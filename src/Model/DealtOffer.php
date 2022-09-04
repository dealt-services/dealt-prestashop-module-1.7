<?php

declare(strict_types=1);
namespace Dealt\Module\Dealtmodule\Model;
use ObjectModel;

class DealtOffer extends ObjectModel
{

    /** @var int Product id */
    public $id_dealt_product;
    /** @var string[] */
    public $title_offer;
    public $dealt_id_offer;
    private $product_price;
    public static $definition = array(
        'table' => 'dealt_offer',
        'primary' => 'id_offer',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(
            /* Classic fields */
            'dealt_id_offer' => array('type' => self::TYPE_STRING, 'required' => true),
            'id_dealt_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            /* Lang fields */
            'title_offer' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 255),
        )
    );


    /**
     * DealtOffer constructor
     *
     * @param null $id Object ID
     * @param null $id_lang Object language
     * @param null $id_shop Object shop
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);

        $this->product_price=$this->getDealtProduct()->price;

        \Shop::addTableAssociation(self::$definition['table'], ['type' => 'shop']);


    }

    public static function findOneByUUID($dealtOfferId)
    {
        $id_offer=\Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT id_offer FROM "._DB_PREFIX_."dealt_offer WHERE dealt_id_offer='".pSQL($dealtOfferId)."'"
        );
        if($id_offer){
            return new DealtOffer($id_offer);
        }
        return null;
    }
    public static function findOneByProduct($dealtProductId)
    {
        $id_offer=\Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT id_offer FROM "._DB_PREFIX_."dealt_offer WHERE id_dealt_product=".(int)$dealtProductId
        );
        if($id_offer){
            return new DealtOffer($id_offer);
        }
        return null;
    }
    public function add($autodate = true, $null_values = false)
    {
        $this->date_add = date('Y-m-d H:i:s');
        $this->date_upd = date('Y-m-d H:i:s');
        if (!parent::add($autodate, $null_values)) {
            return false;
        }
        return true;
    }

    public function update($null_values = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        if (!parent::update($null_values)) {
            return false;
        }
        return true;
    }

    public function getDealtProduct()
    {
        return new \Product($this->id_dealt_product);
    }

}