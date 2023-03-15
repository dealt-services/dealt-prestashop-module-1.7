<?php

declare(strict_types=1);
namespace Dealt\Module\Dealtmodule\Builder;

use Dealt\Module\Dealtmodule\Model\DealtOffer;
use Dealt\Module\Dealtmodule\Utils\DealtTools;
use Dealt\Module\Dealtmodule\Model\DealtOfferCategory;
use DealtModuleLogger;
use Image;

require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtModuleLogger.php');

class DealtOfferBuilder extends AbstractBuilder
{
    private $multiple_value_separator = ',';

    /**
     * @param null $id
     * @return DealtOffer
     */
    public function build($id = null)
    {
        return new DealtOffer($id);
    }

    /**
     * @param $data
     * @return bool|DealtOffer
     */
    public function createOrUpdate($data)
    {
        $id = !empty($data['id_offer']) ? $data['id_offer'] : null;
        $dealt = $this->build($id);
        $dealt->dealt_id_offer = $data['dealt_id_offer'];
        $product = new \Product();
        if ($dealt->id) {
            $product = new \Product($dealt->id_dealt_product);
        }
        //lang fields
        $languages = \Language::getIsoIds(true);
        $name = $data['title_offer_' . \Configuration::get('PS_DEFAULT_LANG')] ?? null;
        if (!empty($name)) {
            $product->name = DealtTools::createMultiLangField($name);
            $dealt->title_offer = DealtTools::createMultiLangField($name);
        }
        foreach ($languages as $lang) {
            $name = !empty($data['title_offer_' . $lang['id_lang']]) ? $data['title_offer_' . $lang['id_lang']] : null;
            $dealt->title_offer[$lang['id_lang']] = $name;
            $product->name[$lang['id_lang']] = $name;
            $link_rewrite = \Tools::link_rewrite($name);
            if ($link_rewrite == '') {
                $link_rewrite = 'friendly-url-autogeneration-failed';
            }
            $product->link_rewrite[$lang['id_lang']] = $link_rewrite;
        }
        //id_category_default
        if (!\Configuration::get('DEALT_MODULE_PRODUCT_CATEGORY')) {
            DealtTools::createDealtCategory();
        }
        $product->id_category_default = \Configuration::get('DEALT_MODULE_PRODUCT_CATEGORY');
        $product->reference = 'DEALT_' . \Tools::passwdGen(7);

        //price
        $product->price = (float)$data['product_price'];
        $product->id_tax_rules_group = 0;
        $product->out_of_stock = 1;

        //shop association
        $shop_is_feature_active = \Shop::isFeatureActive();
        if (!$shop_is_feature_active) {
            $product->shop = (int)\Configuration::get('PS_SHOP_DEFAULT');
            $dealt->shop = (int)\Configuration::get('PS_SHOP_DEFAULT');
        } elseif (!isset($product->shop) || empty($product->shop)) {
            $product->shop = implode($this->multiple_value_separator, Shop::getContextListShopID());
            $dealt->shop = implode($this->multiple_value_separator, Shop::getContextListShopID());
        }

        if (!$shop_is_feature_active) {
            $product->id_shop_default = (int)\Configuration::get('PS_SHOP_DEFAULT');
        } else {
            $product->id_shop_default = (int)\Context::getContext()->shop->id;
        }


        try {
            $product->save();
            if (!$product->id) {
                DealtModuleLogger::log(
                    'Could not create offer',
                    DealtModuleLogger::TYPE_ERROR,
                    ['Errors' => []]
                );
                return false;
            }

            //category product association
            if (empty($data['offer_category_tree'])) {
                $data['offer_category_tree'] = [];
            }
            $categories_ids = array_unique($data['offer_category_tree']);
            DealtTools::setProductCategoriesAssociations($product, $categories_ids);

            //add stock
            \StockAvailable::setProductOutOfStock((int)$product->id, 1);
            \StockAvailable::setQuantity($product->id, 0, 100);

            $dealt->id_dealt_product = $product->id;
            $dealt->save();

            //DealtOffer category
            if ($dealt->id) {
                DealtTools::setOfferCategoryAssociation($dealt, $categories_ids);
                DealtOfferCategory::cleanTreeDifference($dealt->id, $dealt->id_dealt_product, $categories_ids);
            }
            if(isset($_FILES['image']) && $_FILES['image']['name'] != '') {
                $this->saveImage($product);
            }
        } catch (\Exception $e) {
            DealtModuleLogger::log(
                'Could not create offer',
                DealtModuleLogger::TYPE_ERROR,
                ['Errors' => ['message' => $e->getMessage(), 'line' => $e->getLine()]]
            );
            return false;
        }
        return $dealt;
    }

    /**
     * Save the file to the specified path
     * @return bool TRUE on success
     */
    public function saveImage($product)
    {
        $file = [];
        $file['save_path'] = $_FILES['image']['tmp_name'];

        $image = new \Image();
        $image->id_product = (int)($product->id);
        $image->position = Image::getHighestPosition($product->id) + 1;

        if (!\Image::getCover($image->id_product)) {
            $image->cover = 1;
        } else {
            $image->cover = 0;
        }

        if (($validate = $image->validateFieldsLang(false, true)) !== true) {
            $file['error'] = \Tools::displayError($validate);
        }

        if (isset($file['error']) && (!is_numeric($file['error']) || $file['error'] != 0)) {
            return $file;
        }

        if (!$image->add()) {
            $file['error'] = \Tools::displayError('Error while creating additional image');
        } else {
            if (!$new_path = $image->getPathForCreation()) {
                $file['error'] = \Tools::displayError('An error occurred during new folder creation');
                return $file;
            }

            $error = 0;

            if (!\ImageManager::resize($file['save_path'], $new_path . '.' . $image->image_format, null, null, 'jpg', false, $error)) {
                switch ($error) {
                    case \ImageManager::ERROR_FILE_NOT_EXIST :
                        $file['error'] = \Tools::displayError('An error occurred while copying image, the file does not exist anymore.');
                        break;

                    case \ImageManager::ERROR_FILE_WIDTH :
                        $file['error'] = \Tools::displayError('An error occurred while copying image, the file width is 0px.');
                        break;

                    case \ImageManager::ERROR_MEMORY_LIMIT :
                        $file['error'] = \Tools::displayError('An error occurred while copying image, check your memory limit.');
                        break;

                    default:
                        $file['error'] = \Tools::displayError('An error occurred while copying image.');
                        break;
                }
                return $file;
            } else {
                $imagesTypes = \ImageType::getImagesTypes('products');
                $generate_hight_dpi_images = (bool)\Configuration::get('PS_HIGHT_DPI');

                foreach ($imagesTypes as $imageType) {
                    if (!\ImageManager::resize($file['save_path'], $new_path . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
                        $file['error'] = \Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
                        return $file;;
                    }

                    if ($generate_hight_dpi_images) {
                        if (!\ImageManager::resize($file['save_path'], $new_path . '-' . stripslashes($imageType['name']) . '2x.' . $image->image_format, (int)$imageType['width'] * 2, (int)$imageType['height'] * 2, $image->image_format)) {
                            $file['error'] = \Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
                            return $file;
                        }
                    }
                }
            }

            unlink($file['save_path']);
            //Necesary to prevent hacking
            unset($file['save_path']);
            \Hook::exec('actionWatermark', array('id_image' => $image->id, 'id_product' => $product->id));

            if (!$image->update()) {
                $file['error'] = \Tools::displayError('Error while updating status');
                return $file;
            }
            \Db::getInstance()->update(
                'image',
                [
                    'cover' => 1
                ],
                'id_image=' . $image->id
            );

            // Associate image to shop from context
            $shops = \Shop::getContextListShopID();
            $image->associateTo($shops);
            $json_shops = array();
            \Db::getInstance()->update(
                'image_shop',
                [
                    'cover' => 1
                ],
                'id_image=' . $image->id
            );
            foreach ($shops as $id_shop) {
                $json_shops[$id_shop] = true;
            }

            $file['status'] = 'ok';
            $file['id'] = $image->id;
            $file['position'] = $image->position;
            $file['cover'] = $image->cover;
            $file['path'] = $image->getExistingImgPath();
            $file['shops'] = $json_shops;

            @unlink(_PS_TMP_IMG_DIR_ . 'product_' . (int)$product->id . '.jpg');
            @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int)$product->id . '_' . \Context::getContext()->shop->id . '.jpg');
        }
        return $file;
    }

}