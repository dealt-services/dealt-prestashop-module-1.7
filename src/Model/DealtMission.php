<?php
declare(strict_types=1);

namespace Dealt\Module\Dealtmodule\Model;

use ObjectModel;

class DealtMission extends ObjectModel
{

    public $id_shop_group;

    /** @var int */
    public $id_product;

    /** @var int */
    public $id_shop;

    /** @var int */
    public $id_product_attribute;

    /** @var int */
    public $id_offer;

    /** @var int */
    public $id_dealt_product;

    /** @var int Order id */
    public $id_order;

    /** @var string */
    public $dealt_id_mission;

    /** @var string */
    public $dealt_status_mission;

    /** @var float */
    public $dealt_gross_price_mission;

    /** @var float */
    public $dealt_vat_price_mission;

    /** @var float */
    public $dealt_net_price_mission;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    public static $definition = array(
        'table' => 'dealt_mission',
        'primary' => 'id_mission',
        'fields' => array(
            'id_offer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_shop_group' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_product_attribute' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_dealt_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'dealt_id_mission' => array('type' => self::TYPE_STRING),
            'dealt_status_mission' => array('type' => self::TYPE_STRING),
            'dealt_gross_price_mission' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'dealt_vat_price_mission' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'dealt_net_price_mission' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
}