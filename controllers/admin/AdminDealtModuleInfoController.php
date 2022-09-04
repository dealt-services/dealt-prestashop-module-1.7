<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /License
 *
 * International Registered Trademark & Property of INVERTUS, UAB
 */

/**
 * Class AdminDealtModuleInfoController Responsible for info page view
 */
class AdminDealtModuleInfoController extends ModuleAdminController
{
    /** @var bool Parameter used to display BO forms using bootstrap */
    public $bootstrap = true;

    /**
     * Loads CSS and JS files
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(_DEALT_MODULE_CSS_URI_.'info.css');
    }

    /**
     * Assigns page content
     */
    public function postProcess()
    {
        parent::postProcess();

        $this->displayContent();
    }

    /**
     * Displays info page HTML
     *
     * @throws Exception
     * @throws SmartyException
     */
    private function displayContent()
    {
        $this->context->smarty->assign([
            'manual_uri' => _DEALT_MODULE_MANUAL_URI_
        ]);
        $this->content .= $this->context->smarty->fetch(_DEALT_MODULE_TEMPLATES_DIR_.'admin/info.tpl');
    }
}
