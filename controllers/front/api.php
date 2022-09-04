<?php

declare(strict_types=1);

require_once(_DEALT_MODULE_API_DIR_ . 'DealtApiAction.php');

class DealtModuleApiModuleFrontController extends AbstractActionHandlerFrontController
{
    public function getModuleActionsClass()
    {
        return get_class(new DealtAPIAction());
    }

    public function handleAction($action)
    {
        switch ($action) {
            case DealtAPIAction::$OFFER_AVAILABILITY:
                return $this->handleOfferAvailability();

            case DealtAPIAction::$MISSION_WEBHOOK:
                return $this->handleMissionWebhook();
        }

        throw new Exception('something went wrong while handling API action');
    }

    protected function handleOfferAvailability()
    {
        try {
            $client = $this->getClient();
            $dealtOffer = $client->checkAvailability(strval(Tools::getValue('dealt_id_offer')), strval(Tools::getValue('zip_code')));

            if ($dealtOffer == null) {
                throw new Exception('Unable to check offer availability');
            }
            $available=$dealtOffer['response']['available'];
            return array_merge(
                ['available' => $available],
                $available ? [] : ['reason' => $this->trans('Offer unavailable for the requested zip code', [], 'Modules.Dealtmodule.Shop')]
            );
        } catch (Exception $e) {
            return ['available' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Make sure the webhook is triggered with the
     * appropriate header in order to read the JSON body :
     *
     * Content-Type:application/json
     *
     * @return mixed
     */
    protected function handleMissionWebhook()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $dealtMissionId = $data['missionId'] ?? false;
        $dealtMissionStatus = $data['status'] ?? false;

        if ($dealtMissionId == false || $dealtMissionStatus == false) {
            throw new Exception('Dealt webhook failed parsing POST body');
        }

        $this->updateStatusByDealtMissionId($dealtMissionId, $dealtMissionStatus);

        return [
            'dealtMissionId' => $dealtMissionId,
            'dealtMissionStatus' => $dealtMissionStatus,
        ];
    }
    /**
     * @param string $dealtMissionId
     * @param string $status
     *
     * @return DealtMission
     */
    public function updateStatusByDealtMissionId($dealtMissionId, $status)
    {
        $dealtMission= new DealtMission($dealtMissionId);
        if(\Validate::isLoadedObject($dealtMission)){
            $dealtMission->dealt_status_mission=$status;
            $dealtMission->save();
        }

        return $dealtMission;
    }
}
