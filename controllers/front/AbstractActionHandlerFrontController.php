<?php

declare(strict_types=1);


use DealtModule;
use Exception;
use Tools;

abstract class AbstractActionHandlerFrontController extends ModuleFrontController
{
    /** @var DealtModule */
    public $module;

    /**
     * @param string $action
     *
     * @return mixed
     */
    abstract public function handleAction($action);

    /**
     * resolves the actions class for the module
     *
     * @return string
     */
    abstract public function getModuleActionsClass();

    public function initContent()
    {
        $this->ajax = true;
        $this->json = true;
        $this->ssl = true;
        parent::initContent();
    }

    public function postProcess()
    {
        $actionsClass = $this->getModuleActionsClass();
        $action = Tools::getValue('action');

        try {
            if ($action == false) {
                throw new Exception('You must specify an action');
            }
            if (!in_array($action, $actionsClass::cases())) {
                throw new Exception('Unknown action');
            }
            if (Tools::getValue('token') != sha1(_COOKIE_KEY_.$this->module->name)) {
                throw new Exception('Token expired or not valid');
            }
            $result = $this->handleAction($action);

            $this->setResponseHeaders(true);
            $this->ajaxRender(json_encode([
                'ok' => true,
                'action' => $action,
                'result' => $result,
            ]));
        } catch (Exception $e) {
            $this->displayAjaxError($e->getMessage());
        }
    }

    /**
     * @return \Dealt\Module\Dealtmodule\Api\DealtApiHandler
     */
    protected function getClient(){
        return $this->module->getClient();
    }
    /**
     * @param bool $ok
     *
     * @return void
     */
    protected function setResponseHeaders($ok = true)
    {
        ob_get_clean();
        ob_end_clean();
        header('Content-Type: application/json');
        if ($ok) {
            header('HTTP/1.1 200 OK');
        }
    }

    /**
     * @param string $error
     *
     * @return void
     */
    public function displayAjaxError($error)
    {
        $this->setResponseHeaders(false);
        $this->ajaxRender(json_encode([
            'ok' => false,
            'error' => $error,
        ]));

        exit;
    }
}
