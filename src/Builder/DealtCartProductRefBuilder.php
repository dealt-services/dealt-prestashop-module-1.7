<?php

declare(strict_types=1);
namespace Dealt\Module\Dealtmodule\Builder;

require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtModuleLogger.php');

use Dealt\Module\Dealtmodule\Model\DealtCartProductRef;
use Dealt\Module\Dealtmodule\Model\DealtOffer;

class DealtCartProductRefBuilder extends AbstractBuilder
{
    /**
     * @param null $id
     * @return DealtCartProductRef
     */
    public function build($id = null)
    {
        return new DealtCartProductRef($id);
    }

    /**
     * @param $data
     * @return bool|DealtOffer
     */
    public function createOrUpdate($data)
    {

        try {
            $id = !empty($data['id_dealt_cart_product_ref']) ? $data['id_dealt_cart_product_ref'] : null;
            $dealtCartRef = $this->build($id);
            $dealtCartRef->id_cart = $data['id_cart'];
            $dealtCartRef->id_product = $data['id_product'];
            $dealtCartRef->id_product_attribute = $data['id_product_attribute'];
            $dealtCartRef->id_offer = $data['id_offer'];
            $dealtCartRef->save();

        } catch (\Exception $e) {
            DealtModuleLogger::log(
                'Could not create dealt_cart_product_ref',
                DealtModuleLogger::TYPE_ERROR,
                ['Errors' => ['message' => $e->getMessage(), 'line' => $e->getLine()]]
            );
            return false;
        }
        return $dealtCartRef;
    }

    /**
     * @param $data
     * @return bool
     */
    public function removeRow($data){
        return \Db::getInstance()->execute(
            "DELETE FROM "._DB_PREFIX_."dealt_cart_product_ref 
                 WHERE id_cart=".(int)$data['id_cart']."
                 AND id_offer=".(int)$data['id_offer']."
                 AND id_product=".(int)$data['id_product']."
                 AND id_product_attribute=".(int)$data['id_product_attribute']." LIMIT 1
                 "
        );
    }


}