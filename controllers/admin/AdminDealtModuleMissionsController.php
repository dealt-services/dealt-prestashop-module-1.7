<?php

use Dealt\Module\Dealtmodule\Api\DealtAPIAction;
use Dealt\Module\Dealtmodule\Model\DealtMission;
use Dealt\Module\Dealtmodule\Model\DealtOffer;

/**
 * Class AdminDealtModuleMissionsController
 */
class AdminDealtModuleMissionsController extends ModuleAdminController
{
    /**
     * AdminDealtModuleMissionsController constructor.
     */
    public function __construct()
    {
        $this->table = 'dealt_mission';
        $this->className = DealtMission::class;
        $this->identifier = 'id_mission';
        $this->bootstrap = true;

        parent::__construct();

        $this->initList();
    }

    public function postProcess()
    {
        parent::postProcess();
        if (!empty(Tools::getValue('action'))) {
            $this->handleMissionAction();
        }
    }

    protected function handleMissionAction()
    {
        $action = Tools::getValue('action');
        $missionId = Tools::getValue('id_mission');
        if ($action == null || $missionId == null) {
            return;
        }

        switch ($action) {
            case 'resubmit':
                $this->handleResubmit($missionId);
                break;
            case 'cancel':
                $this->handleCancel($missionId);
                break;
        }
    }

    /**
     * @param $missionId
     * @return DealtMission|void
     */
    protected function handleResubmit($missionId)
    {
        $mission = new DealtMission($missionId);

        /* can only resubmit if mission was canceled */
        if (!Validate::isLoadedObject($mission) || ($mission->dealt_status_mission != 'CANCELLED')) {
            $this->errors[]=$this->l('Only Canceled missions can be resubmitted');
            return;
        }

        $offer = new DealtOffer($mission->id_offer);
        $order = new Order($mission->id_order);
        $product = new Product($mission->id_product);
        $apiHandler = $this->module->getClient();
        $result = $apiHandler->submitMission($offer, $order, $product);
        if ($result == null) {
            return;
        }

        $mission->dealt_id_mission = $result->id;
        $mission->dealt_status_mission = $result->status;

        $mission->save();

        return $mission;
    }

    protected function handleCancel($missionId)
    {
        $mission = new DealtMission($missionId);

        /* can only cancel if mission was submitted */
        if (!Validate::isLoadedObject($mission) || ($mission->dealt_status_mission != 'SUBMITTED')) {
            $this->errors[]=$this->l('Only Submitted missions can be canceled');
            return;
        }
        $apiHandler = $this->module->getClient();
        $result = $apiHandler->cancelMission($mission->dealt_id_mission);
        if ($result == null) {
            return;
        }
        if(is_string($result)){
            $this->errors[]=$result;
            return;
        }
        $mission->dealt_status_mission=$result->status;
        $mission->save();

        return $mission;
    }

    /**
     * Collects bid range list data
     */
    private function initList()
    {
        $this->list_no_link = true;
        $this->fields_list = [
            'id_order' => array(
                'title' => $this->l('Order Id'),
            ),
            'dealt_status_mission' => [
                'title' => $this->l('Mission status'),
                'callback' => 'getStatus',
            ],
            'id_offer' => [
                'title' => $this->l('Offer'),
                'havingFilter' => true,
                'filter_key' => 'offer_name',
                'callback' => 'getOfferLink',
            ],
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
            'dol.title_offer as offer_name, pl.`name` as `product_name`';
        $this->_join = '
            JOIN `' . _DB_PREFIX_ . 'product` p
                ON (p.`id_product` = a.`id_product`)
            JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON (pl.`id_product` = a.`id_dealt_product`
                    AND `id_lang` = "' . (int)$this->context->language->id . '"
                    AND pl.`id_shop` = "' . (int)$this->context->shop->id . '")
            JOIN `' . _DB_PREFIX_ . 'dealt_offer_lang` dol
                ON (dol.`id_offer` = a.`id_offer`
                    AND dol.`id_lang` = "' . (int)$this->context->language->id . '"
                    AND dol.`id_shop` = "' . (int)$this->context->shop->id . '")
            ' . Shop::addSqlAssociation('dealt_offer', 'a') . '                    
        ';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';

    }

    public function renderList()
    {
        $this->addRowAction('resubmit');
        $this->addRowAction('cancel');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function displayResubmitLink($token, $id, $name = null)
    {
        $this->context->smarty->assign([
            'href' => $this->context->link->getAdminLink('AdminDealtModuleMissions') . '&action=resubmit&id_mission=' . (int)$id . '&token=' . Tools::getValue('token'),
            'action' => $this->l('Resubmit'),
            'id' => $id,
        ]);
        return $this->context->smarty->fetch(_DEALT_MODULE_TEMPLATES_DIR_ . 'admin/resubmit.tpl');
    }

    public function displayCancelLink($token, $id, $name = null)
    {
        $this->context->smarty->assign([
            'href' => $this->context->link->getAdminLink('AdminDealtModuleMissions') . '&action=cancel&id_mission=' . (int)$id . '&token=' . Tools::getValue('token'),
            'action' => $this->l('Cancel'),
            'id' => $id,
        ]);
        return $this->context->smarty->fetch(_DEALT_MODULE_TEMPLATES_DIR_ . 'admin/cancel.tpl');
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

    public function getOfferLink($id)
    {
        if (!empty($id) && (int)$id) {
            $dealtOffer = new DealtOffer($id);
            return '<a href="' . $this->context->link->getAdminLink('AdminDealtModuleDeals') . '&id_offer=' . (int)$id . '&updatedealt_offer">' . $dealtOffer->title_offer[$this->context->language->id] ?? $id . '</a>';
        } else {
            return 0;
        }
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getStatus($value)
    {
        if ($value !== 'SUBMITTED') {
            return '<span class="label color_field" style="background-color:#DC143C;color:white"> ' . $value . '</span>';
        }

        return '<span class="label color_field" style="background-color:#32CD32;color:#383838"> ' . $value . '</span>';
    }


}
