<?php
declare(strict_types=1);

namespace Dealt\Module\Dealtmodule\Utils;

use Dealt\Module\Dealtmodule\Model\DealtCartProductRef;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Dealt\Module\Dealtmodule\Model\DealtOffer;

class DealtTools
{
    public static $DEALT_PRODUCT_CATEGORY_NAME = '__dealt__';

    /**
     * Helper function to create multilang string
     *
     * @param string $field
     *
     * @return mixed
     */
    public static function createMultiLangField($field)
    {
        $res = array();
        foreach (\Language::getIDs(false) as $id_lang) {
            $res[$id_lang] = $field;
        }

        return $res;
    }

    /**
     * Converts a price string to the PS standard way
     * of storing prices in DB
     *
     * @param string $priceString
     *
     * @return float
     */
    public static function formatPriceForDB(string $priceString)
    {
        return floatval($priceString);
    }

    /**
     * Checks wether a string is a valid UUID v4
     *
     * @param string $uuid
     *
     * @return bool
     */
    public static function isValidUUID(string $uuid)
    {
        $UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

        return (bool)preg_match($UUIDv4, $uuid);
    }


    /**
     * Iterates over the products in the current context's
     * cart and returns the first match
     *
     * @param Cart $cart
     * @param int $productId
     * @param int|null $productAttributeId
     *
     * @return mixed|null
     */
    public static function getProductFromCart(\Cart $cart, $productId, $productAttributeId = null)
    {
        $cartProducts = $cart->getProducts();
        if (!empty($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                if (
                    (int)$cartProduct['id_product'] == $productId &&
                    ($productAttributeId == null || ((int)$cartProduct['id_product_attribute'] == $productAttributeId))
                ) {
                    return $cartProduct;
                }
            }
        }

        return null;
    }

    /**
     * @param Cart $cart
     * @param $productId
     * @param null $productAttributeId
     * @return int
     */
    public static function alreadyInCart(\Cart $cart, $productId, $productAttributeId=null){

        return (bool)\Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT quantity FROM " . _DB_PREFIX_ . "cart_product 
                    WHERE id_cart=" . (int)$cart->id . "
                    AND id_product=" . (int)$productId . "
                    AND id_product_attribute=" . (int)$productAttributeId . "
                    "
        );
    }
    /**
     * Creates an indexed multi-dimensional array of the current cart
     * [productId][attributeId] product
     * Useful for quick lookup.
     *
     * @param Cart $cart
     *
     * @return array<int, array<int, mixed>>
     */
    public static function indexCartProducts(\Cart $cart)
    {
        $cartProducts = [];

        foreach ($cart->getProducts() as $cartProduct) {
            $productId = $cartProduct['id_product'];
            $productAttributeId = $cartProduct['id_product_attribute'];

            if (!isset($cartProducts[$productId])) {
                $cartProducts[$productId] = [];
            }
            $cartProducts[$productId][$productAttributeId] = $cartProduct;
        }

        return $cartProducts;
    }

    /**
     * Based on the current context, we must resolve
     * the product attribute id differently
     * - on ajax refresh : we only have the group values
     *
     * @param int|false $productAttributeId
     * @param int|int[]|false|null $groupValues
     *
     * @return int|null
     */
    public static function resolveProductAttributeId($productId, $productAttributeId, $groupValues)
    {
        if ($productAttributeId != false) {
            return $productAttributeId;
        }
        if (!isset($groupValues) || $groupValues == false) {
            return null;
        }

        return \Product::getIdProductAttributeByIdAttributes(
            $productId,
            $groupValues
        );
    }

    /**
     * @param string $phoneNumber
     * @param string $countryCode
     *
     * @return string|false
     */
    public static function formatPhoneNumberE164($phoneNumber, $countryCode)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $proto = $phoneUtil->parse($phoneNumber, $countryCode);
            return $phoneUtil->format($proto, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * @param product $productObj
     * @param array $categories_ids
     *
     * @return product
     */
    public static function setProductCategoriesAssociations(\Product $productObj, array $categories_ids)
    {
        foreach ($categories_ids as $id) {
            $catObject = new \Category($id);

            if (!\Validate::isLoadedObject($catObject)) {
                continue;
            }

            $maxpos = (int)\Db::getInstance()->getValue('SELECT MAX(position) as maxpos FROM ' . _DB_PREFIX_ . 'category_product WHERE id_category=' . (int)$catObject->id);
            //insert product category assossiations
            $sql_values = '(' . (int)$catObject->id_category . ', ' . (int)$productObj->id . ', ' . ($maxpos + 1) . ')';

            if (!empty($sql_values)) {
                try {
                    \Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'category_product` (`id_category`, `id_product`, `position`) VALUES ' . $sql_values);
                } catch (\Exception $e) {
                    DealtModuleLogger::log(
                        'Could not create category',
                        DealtModuleLogger::TYPE_ERROR,
                        ['Errors' => ['message' => $e->getMessage(), 'line' => $e->getLine()]]
                    );
                }
            }
        }

        return $productObj;
    }

    /**
     * @param DealtOffer $Offer
     * @param array $categories_ids
     * @return DealtOffer
     */
    public static function setOfferCategoryAssociation(DealtOffer $Offer, array $categories_ids)
    {
        foreach ($categories_ids as $id) {
            $catObject = new \Category($id);

            if (!\Validate::isLoadedObject($catObject)) {
                continue;
            }

            //insert product category assossiations
            $sql_values = '(' . (int)$Offer->id . ', ' . (int)$Offer->id_dealt_product . ', ' . (int)$catObject->id . ')';

            if (!empty($sql_values)) {
                try {
                    \Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'dealt_offer_category` (`id_offer`, `id_dealt_product`, `id_category`) VALUES ' . $sql_values);
                } catch (\Exception $e) {
                    DealtModuleLogger::log(
                        'Could not create category offer association',
                        DealtModuleLogger::TYPE_ERROR,
                        ['Errors' => ['message' => $e->getMessage(), 'line' => $e->getLine()]]
                    );
                }
            }
        }

        return $Offer;
    }

    /**
     * Create the internal DealtModule category
     * -> used for virtual dealt products
     *
     * @return bool
     */
    public static function createDealtCategory()
    {
        $match = \Category::searchByName(\Context::getContext()->language->id, static::$DEALT_PRODUCT_CATEGORY_NAME, true, true);

        if (!empty($match)) {
            return true;
        }
        $category = new \Category();
        $category->name = DealtTools::createMultiLangField(static::$DEALT_PRODUCT_CATEGORY_NAME);
        $category->link_rewrite = DealtTools::createMultiLangField(\Tools::link_rewrite(static::$DEALT_PRODUCT_CATEGORY_NAME));
        $category->active = false;
        $category->id_parent = \Category::getRootCategory()->id;
        $category->description = 'Internal DealtModule category used for Dealt offer virtual products';

        $category->add();
        if ($category->id) {
            \Configuration::updateValue('DEALT_MODULE_PRODUCT_CATEGORY', $category->id);
            return true;
        }

        return false;
    }

    /**
     * Deletes the DealtModule internal category
     *
     * @return bool
     */
    public static function deleteDealtCategory()
    {
        $match = \Category::searchByName(\Context::getContext()->language->id, static::$DEALT_PRODUCT_CATEGORY_NAME, true, true);

        if (!empty($match)) {
            $category = new \Category($match['id_category']);
            \Configuration::deleteByName('DEALT_MODULE_PRODUCT_CATEGORY');
            return $category->delete();
        }

        return true;
    }

    /**
     * @param mixed $quantity
     *
     * @return string
     */
    public static function getFormattedPrice(DealtOffer $offer, $id_currency, $quantity = 1)
    {
        $quantity = (int)($quantity == false ? 1 : $quantity);
        $currency = new \Currency($id_currency);

        return \Tools::displayPrice(
            self::getPrice($offer->id_dealt_product, 0, $quantity),
            $currency
        );
    }

    /**
     * @param $id_product
     * @param $id_product_attribute
     * @param mixed $quantity
     *
     * @return float
     */
    public static function getPrice($id_product, $id_product_attribute, $quantity = 1)
    {
        if (empty(\Context::getContext()->employee)) {
            \Context::getContext()->employee = new Employee(1);
        }

        return \Product::getPriceStatic(
            $id_product,
            true,
            $id_product_attribute,
            6,
            null,
            false,
            true,
            $quantity
        );
    }

    /**
     * Resolves the dealt offers from the current
     * cart products.
     *
     *
     * @param Cart $cart
     * @return array|\PrestaShopCollection
     */
    public static function getDealtOffersFromCart(\Cart $cart)
    {
        try {
            $cartProducts = $cart->getProducts();
            $cartProductIds = array_map(function ($cartProduct) {
                return (int)$cartProduct['id_product'];
            }, $cartProducts);
            if (count($cartProductIds) <= 0) {
                return [];
            }

            $id_lang = \Context::getContext()->language->id;
            $offers = new \PrestaShopCollection(DealtOffer::class, $id_lang);
            $offers->where('id_dealt_product', 'in', $cartProductIds);
            return $offers;

        } catch (\Exception $e) {
            /* cart may not exist yet in DB and will make internal cart methods crash */
            DealtModuleLogger::log('Something went wrong when in offer search from cart', DealtModuleLogger::TYPE_ERROR, ['Error' => $e]);
        }
        return [];
    }

    /**
     * @param $cartId
     * @param $offer
     * @return \PrestaShopCollection
     */
    public static function getDealtCartRef($cartId, $offer)
    {
        $dealtCartRefs = new \PrestaShopCollection(DealtCartProductRef::class);
        $dealtCartRefs->where('id_cart', '=', (int)$cartId);
        $dealtCartRefs->where('id_offer', '=', (int)$offer->id);
        return $dealtCartRefs->getFirst();
    }

}
