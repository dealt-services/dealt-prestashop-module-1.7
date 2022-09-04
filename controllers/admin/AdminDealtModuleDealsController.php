<?php

use Dealt\Module\Dealtmodule\Utils\DealtTools;
use Dealt\Module\Dealtmodule\Model\DealtOffer;
use Dealt\Module\Dealtmodule\Model\DealtOfferCategory;
/**
 * Class AdminDealtModuleDealsController
 */
class AdminDealtModuleDealsController extends ModuleAdminController
{
    private $builder;

    /**
     * AdminDealtModuleDealsController constructor
     */
    public function __construct()
    {
        $this->table = 'dealt_offer';
        $this->className = DealtOffer::class;
        $this->identifier = 'id_offer';
        $this->bootstrap = true;

        parent::__construct();


        $this->initForm();
        $this->initList();
        $this->builder = $this->module->getBuilder('DealtOffer');
    }

    /**
     * Loads CSS and JS files
     */
    public function setMedia($isNewTheme = false)
    {
        $this->addJquery();
        $this->addJS(_PS_BO_ALL_THEMES_DIR_ . 'default/js/tree.js');
        parent::setMedia($isNewTheme);

        $this->addJS(_DEALT_MODULE_JS_URI_ . 'dealtmodule.js');
    }

    /**
     * Object creation
     *
     * @return false|ObjectModel Object created successfully
     */
    public function processAdd()
    {
        if (Tools::isSubmit('submitAdddealt_offer')) {
            $this->validateFields();
        }
        if (!empty($this->errors)) {
            return;
        }
        $this->builder->createOrUpdate(Tools::getAllValues());
        if (empty($this->errors)) {
            $this->confirmations[] = $this->l('The offer has successfully created');
        }

    }


    /**
     * Object update
     *
     * @return false|ObjectModel|void Object updated successfully
     */
    public function processUpdate()
    {
        $this->validateFields();
        if (!empty($this->errors)) {
            return;
        }
        $this->builder->createOrUpdate(Tools::getAllValues());
        if (empty($this->errors)) {
            $this->confirmations[] = $this->l('The offer has successfully updated');
        }
    }

    /**
     * Collects bid range list data
     */
    private function initList()
    {
        $this->list_no_link = true;
        $this->fields_list = [

            'id_offer' => [
                'title' => $this->l('Title'),
                'havingFilter' => true,
                'filter_key' => 'dol!title_offer',
                'callback' => 'getOfferName',
            ],
            'dealt_id_offer' => array(
                'title' => $this->l('Offer ID'),
            ),
            'category_count' => array(
                'title' => $this->l('Total categories'),
                'havingFilter' => true
            ),
            'id_dealt_product' => [
                'title' => $this->l('Product'),
                'havingFilter' => true,
                'filter_key' => 'product_name',
                'callback' => 'getProductLink',
            ],
            'date_add' => [
                'title' => $this->l('Date add'),
                'filter_key' => 'a!date_add',
                'type' => 'datetime'
            ]
        ];
        $this->_select =
            'dol.title_offer, pl.`name` as `product_name`, count(1) as `category_count`, p.price as product_price';

        $this->_join = '
            JOIN `' . _DB_PREFIX_ . 'product` p
                ON (p.`id_product` = a.`id_dealt_product`)
            JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON (pl.`id_product` = a.`id_dealt_product`
                    AND `id_lang` = "' . (int)$this->context->language->id . '"
                    AND `id_shop` = "' . (int)$this->context->shop->id . '")
            JOIN `' . _DB_PREFIX_ . 'dealt_offer_lang` dol
                ON (dol.`id_offer` = a.`id_offer`
                    AND dol.`id_lang` = "' . (int)$this->context->language->id . '"
                    AND dol.`id_shop` = "' . (int)$this->context->shop->id . '")
            JOIN ' . _DB_PREFIX_ . 'dealt_offer_shop dealt_offer_shop
		ON (dealt_offer_shop.id_offer = a.id_offer AND dealt_offer_shop.id_shop = "' . (int)$this->context->shop->id . '")                
            LEFT JOIN `' . _DB_PREFIX_ . 'dealt_offer_category` doc
                ON (doc.`id_offer` = a.`id_offer`)        
        ';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->_group .= 'GROUP BY a.id_offer';
        $this->actions = ['edit', 'delete'];

    }

    public function getProductLink($id)
    {
        if (!empty($id) && (int)$id) {
            $product = new \Product($id);
            return '<a href="' . $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . (int)$id . '&updateproduct">' . $product->name[$this->context->language->id] ?? $id . '</a>';
        } else {
            return 0;
        }
    }

    public function getOfferName($id)
    {
        if (!empty($id) && (int)$id) {
            $offer = new DealtOffer($id);
            return $offer->title_offer[$this->context->language->id];
        } else {
            return '';
        }
    }

    private function initForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return;
        }
        $root = Category::getRootCategory();
        $tree = new HelperTreeCategories('offer_category_tree');

        $tree->setUseCheckBox(true)
            ->setAttribute('is_category_filter', $root->id)
            ->setRootCategory($root->id)
            ->setFullTree(true)
            ->setSelectedCategories(DealtOfferCategory::getOfferCategories(Tools::getValue('id_offer')))
            ->setInputName('offer_category_tree'); //Set the name of input. The option "name" of $fields_form doesn't seem to work with "categories_select" type
        $selected_categories = DealtOfferCategory::getOfferCategories(Tools::getValue('id_offer'));
        $cover=\Product::getCover((int)$obj->id_dealt_product);
        $path_to_image = _PS_IMG_DIR_.'p/'.\Image::getImgFolderStatic($cover['id_image']).(int)$cover['id_image'].'.jpg';
        $image_url = \ImageManager::thumbnail($path_to_image, 'product_'.(int)$obj->id_dealt_product.'.'.$this->imageType, 350,
            $this->imageType, true, true);

        $image_size = file_exists($path_to_image) ? filesize($path_to_image) / 1000 : false;
        \Tools::getValue('id_offer');
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Dealt offer'),
                'icon' => 'icon-coins'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Title'),
                    'name' => 'title_offer',
                    'lang' => true,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Offer UUID'),
                    'col' => '4',
                    'name' => 'dealt_id_offer',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Offer price € (tax excl.)'),
                    'name' => 'product_price',
                    'required' => true
                ],
                [
                    'type' => 'hidden',
                    'label' => $this->l('Offer price € (tax excl.)'),
                    'name' => 'id_offer',
                    'required' => true
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Product Cover Image'),
                    'name' => 'image',
                    'display_image' => true,
                    'image' => $image_url ? $image_url : false,
                    'size' => $image_size,
                    'hint' => $this->l('This is the main image for the dealt offer.'),
                ],
                [
                    'type' => 'categories',
                    'label' => $this->l('Display in category'),
                    'multiple' => true,
                    'name' => 'offer_category_tree',
                    'tree' => array(
                        'id' => 'id',
                        'use_checkbox' => true,
                        'use_search' => true,
                        'selected_categories' => $selected_categories,
                        'root_category' => $root->id,
                    )
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];
    }

    private function validateFields()
    {
        if (empty($this->context)) {
            $this->context = \Context::getContext();
        }
        $dealtUUID = Tools::getValue('dealt_id_offer');
        $name = Tools::getValue('title_offer_' . Configuration::get('PS_LANG_DEFAULT'));

        if (!DealtTools::isValidUUID($dealtUUID)) {
            $this->errors[] = $this->l('The UUID is not a valid UUID v4');
        }

        if (!empty($name) && empty(Tools::getValue('id_offer'))) {
            $product_exist = (bool)\Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                "SELECT 1  FROM " . _DB_PREFIX_ . "product_lang pl
                INNER JOIN " . _DB_PREFIX_ . "product p USING (id_product)
                WHERE p.active=1 
                      AND `name`='" . pSQL($name) . "' 
                      AND id_shop=" . (int)$this->context->shop->id . "
                      AND id_lang=" . (int)$this->context->language->id . "
                "
            );
            if ($product_exist) {
                $this->errors[] = $this->l('We found another product with this name please change name');
            }
        }
    }
}
