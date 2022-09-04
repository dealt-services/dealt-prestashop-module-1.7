<?php

declare(strict_types=1);

namespace Dealt\Module\Dealtmodule\Model;

use ObjectModel;

class DealtCartProductRef extends ObjectModel
{
    /** @var int Cart id */
    public $id_cart;
    /** @var int Product id */
    public $id_product;
    /** @var int Combination id */
    public $id_product_attribute;
    /** @var int Offer id */
    public $id_offer;

    public static $definition = array(
        'table' => 'dealt_cart_product_ref',
        'primary' => 'id_dealt_cart_product_ref',
        'fields' => array(
            'id_cart' =>               array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_product' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_product_attribute' =>  array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_offer' =>              array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
        )
    );

    /**
     * @param $id_cart
     * @param $id_product
     * @param $id_offer
     * @param int $id_product_attribute
     * @return DealtCartProductRef|null
     */
    public static function searchByCriteria($id_cart, $id_product, $id_offer, $id_product_attribute=0){
        $id_dealt_cart_product_ref=\Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT id_dealt_cart_product_ref FROM "._DB_PREFIX_."dealt_cart_product_ref
                 WHERE id_cart=".(int)$id_cart."
                 AND id_product=".(int)$id_product."
                 AND id_offer=".(int)$id_offer."
                 AND id_product_attribute=".(int)$id_product_attribute."
            "
        );
        if(!empty($id_dealt_cart_product_ref)){
            return new self($id_dealt_cart_product_ref);
        }
        return null;
    }
}