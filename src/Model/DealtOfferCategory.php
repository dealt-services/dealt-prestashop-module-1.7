<?php

declare(strict_types=1);
namespace Dealt\Module\Dealtmodule\Model;
use ObjectModel;

class DealtOfferCategory extends ObjectModel
{

    /** @var int Product id */
    public $id_dealt_product;
    /** @var int Category id */
    public $id_category;
    /** @var int Offer id */
    public $id_offer;

    public static $definition = array(
        'table' => 'dealt_offer_category',
        'primary' => 'id_dealt_offer_category',
        'fields' => array(
            'id_dealt_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_category' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_offer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
        )
    );

    public static function getOfferCategories($id_offer = null)
    {
        if ($id_offer) {
            $res = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_category FROM ' . _DB_PREFIX_ . 'dealt_offer_category WHERE id_offer=' . (int)$id_offer
            );
            if (!empty($res)) {
                return array_column($res, 'id_category');
            }
        }
        return array((int)\Configuration::get('DEALT_MODULE_PRODUCT_CATEGORY'));
    }

    /**
     * @param $categories
     * @return bool|false|string|null
     */
    public static function getProductOffer(\Product $product)
    {
        $cache_id = 'DealtOfferCategory::getProductOffer_' . (int)$product->id;
        if (!\Cache::isStored($cache_id)) {
            $id_offer = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT id_offer FROM ' . _DB_PREFIX_ . 'dealt_offer_category doc
                 INNER JOIN ' . _DB_PREFIX_ . 'category_product cp ON (cp.id_category=doc.id_category)
                 WHERE cp.id_product =' . (int)$product->id . '
                 ORDER BY id_offer DESC
                 '
            );
            if (!empty($id_offer)) {
                \Cache::store($cache_id, $id_offer);
            }
            return $id_offer;

        }
        return \Cache::retrieve($cache_id);

    }

    /**
     * @param $id_offer
     * @param $id_dealt_product
     * @param array $selected_categories
     * @return bool
     */
    public static function cleanTreeDifference($id_offer, $id_dealt_product, array $selected_categories)
    {
        $where = '';
        if (!empty($selected_categories)) {
            $where .= ' AND id_category NOT IN(' . implode(",", $selected_categories) . ')';
        }
        return \Db::getInstance()->execute(
                'DELETE FROM ' . _DB_PREFIX_ . self::$definition['table'] . ' 
            WHERE id_offer=' . (int)$id_offer . ' 
            AND id_dealt_product=' . (int)$id_dealt_product .
                $where
            ) && \Db::getInstance()->execute(
                'DELETE FROM ' . _DB_PREFIX_ . 'category_product 
            WHERE id_product=' . (int)$id_dealt_product .
                $where
            );
    }
}