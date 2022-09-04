<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use Dealt\Module\Dealtmodule\Api\DealtApiHandler;
use Dealt\Module\Dealtmodule\Model\DealtOffer;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
use Dealt\Module\Dealtmodule\Builder\BuilderFactory;

class Dealtmodule extends Module
{
    protected $config_form = false;
    /**
     * Module main tab controller name
     */
    const CONTROLLER_MODULE = 'AdminDealtModule';

    /**
     * Module auctions tab missions name
     */
    const CONTROLLER_MISSIONS = 'AdminDealtModuleMissions';
    /**
     * Module auctions tab missions name
     */
    const CONTROLLER_DEALS = 'AdminDealtModuleDeals';

    /**
     * Module settings tab controller name
     */
    const CONTROLLER_CONFIGURATION = 'AdminDealtModuleConfiguration';
    /**
     * Module info tab controller name
     */
    const CONTROLLER_INFO = 'AdminDealtModuleInfo';
    /**
     * @var DealtApiHandler
     */
    private $client;


    public function __construct()
    {
        $this->name = 'dealtmodule';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Dealt';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Dealt Module');
        $this->description = $this->l('The official Dealt prestashop module.');

        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => '1.7.5.9');
        $this->loadModuleClasses();
        if(!$this->context->employee){
          $this->setEmployee();
        }
    }

    /**
     * Module main installation function
     *
     * @return bool module installed successfully or not
     */
    public function install()
    {
        if (!parent::install()) {
            $this->_errors[] = $this->l('Could not install module');

            return false;
        }

        require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtModuleInstaller.php');

        $installer = new DealtModuleInstaller;
        $installer->install($this);

        $this->_errors = array_merge($this->_errors, $installer->errors);

        if (count($this->_errors) === 0) {
            DealtModuleLogger::log(
                'Module installed successfully',
                DealtModuleLogger::TYPE_SUCCESS
            );
        } else {
            DealtModuleLogger::log(
                'Could not install module',
                DealtModuleLogger::TYPE_ERROR,
                ['Errors' => json_encode($this->_errors)]
            );
        }

        return count($this->_errors) === 0;
    }

    /**
     * Module main uninstall function
     *
     * @return bool module uninstalled successfully or not
     */
    public function uninstall()
    {
        require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtModuleInstaller.php');

        $installer = new DealtModuleInstaller;
        $installer->uninstall($this);

        $this->_errors = array_merge($this->_errors, $installer->errors);

        if (count($this->_errors) !== 0) {
            return false;
        }

        if (!parent::uninstall()) {
            $this->_errors[] = $this->l('Could not uninstall module');

            return false;
        }

        return true;
    }
    /**
     * @return void
     */
    public function hookActionFrontControllerSetMedia()
    {
        Media::addJsDef(['DealtGlobals' => [
            'actions' => [
                'cart' => \Context::getContext()->link->getModuleLink(
                    strtolower(DealtModule::class),
                    'cart',
                    ['ajax' => true]
                )
            ],
        ]]);
        if (method_exists($this->context->controller, 'registerJavascript')) {
            $this->context->controller->registerJavascript(
                'modules-' . $this->name . '-dealtmodule',
                'modules/' . $this->name . '/views/js/dealtmodule.js',
                ['position' => 'bottom', 'priority' => 150]
            );
            $this->context->controller->registerJavascript(
                'modules-' . $this->name . '-dealtmodule-autocomplete',
                'modules/' . $this->name . '/views/js/front/utils/zipcode.autocomplete.js',
                ['position' => 'bottom', 'priority' => 200]
            );
        } else {
            $this->context->controller->addJS(
                $this->getPathUri() . 'views/js/dealtmodule.js?version=' . $this->version,
                false
            );

            $this->context->controller->addJS(
                $this->getPathUri() . 'views/js/front/utils/zipcode.autocomplete.js?version=' . $this->version,
                false
            );
        }

        if (method_exists($this->context->controller, 'registerStylesheet')) {
            $this->context->controller->registerStylesheet(
                'modules-' . $this->name . '-dealtmodule',
                'modules/' . $this->name . '/views/css/dealtmodule.css',
                ['media' => 'all', 'priority' => 150]
            );
        } else {
            $this->context->controller->addCss(
                $this->getPathUri() . 'views/css/dealtmodule.css?version=' . $this->version,
                'all',
                null,
                false
            );
        }

    }
    /**
     * Module hook used to load CSS and JS files
     */
    public function hookDisplayHeader()
    {
        return $this->displayScriptVariables();
    }

    /**
     * Displays additional needed script variables
     *
     * @return string HTML content with script variables
     * @throws Exception
     * @throws SmartyException
     */
    private function displayScriptVariables()
    {
        $this->context->smarty->assign([
            'dealt_module_ajax_uri' => _DEALT_MODULE_AJAX_URI_,
            'dealt_module_js_uri' => _DEALT_MODULE_JS_URI_,
            'dealt_module_ajax_token' => sha1(_COOKIE_KEY_ . $this->name),
            'dealt_module_customer' => (int)$this->context->customer->id,
            'dealt_module_currency' => (int)$this->context->currency->id,
            'dealt_module_shop' => (int)$this->context->shop->id,
            'dealt_module_lang' => (int)$this->context->language->id,
            'dealt_module_cart' => (int)$this->context->cart->id,
        ]);

        return $this->context->smarty->fetch(_DEALT_MODULE_TEMPLATES_DIR_ . 'front/ScriptVariables.tpl');
    }

    /**
     * Main module function to display content
     */
    public function getContent()
    {
        $url = $this->context->link->getAdminLink(self::CONTROLLER_DEALS);

        Tools::redirectAdmin($url);
    }

    /**
     * Includes classes which are used in module
     */
    private function loadModuleClasses()
    {
        require_once(dirname(__FILE__) . '/dealtmodule.config.php');
        require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtModuleLogger.php');
        require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtCart.php');
        require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtCheckoutValidation.php');
        require_once(_DEALT_MODULE_CLASSES_DIR_ . 'DealtPresenter.php');
    }


    /**
     * @return DealtApiHandler
     */
    public function getClient()
    {
        if (null === $this->client) {
            try {
                $this->client = $this->get('dealt.modules.dealmodule.api.handler');
            } catch (Throwable $e) {
                try {
                    $container = SymfonyContainer::getInstance();
                    if (null !== $container) {
                        $this->client = $container->get('dealt.modules.dealmodule.api.handler');
                    }
                } catch (Throwable $e) {
                }
            }
        }

        // Container is not available so we use legacy repository as fallback
        if (!$this->client) {
            $this->client = new DealtApiHandler(
                $this,
                new \Dealt\Module\Dealtmodule\Api\DealtEnv()
            );
        }

        return $this->client;
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if (Tools::getValue('controller') === 'AdminDealtModuleDeals') {
            $this->context->controller->addJquery();
        }
        $this->context->controller->addCSS(_DEALT_MODULE_CSS_URI_ . 'menuTabIcon.css');

    }

    public function hookActionAdminDealtModuleDealsFormModifier($params)
    {
        $id_offer = $params['fields_value']['id_offer'];
        $dealt = new DealtOffer($id_offer);
        if (Validate::isLoadedObject($dealt)) {
            $params['fields_value']['product_price'] = Product::getPriceStatic($dealt->id_dealt_product, false);
        }
    }
    /**
     * hookDisplayProductExtraContent
     *
     * Extra actions on the product page (right column).
     *
     **/
    public function hookDisplayProductExtraContent($params)
    {
        return $this->updateDealtBlock($params);
    }
    /**
     * hookDisplayProductExtraContent
     *
     * Extra actions on the product page (right column).
     *
     **/
    public function hookDisplayProductAdditionalInfo($params)
    {
      return $this->updateDealtBlock($params);
    }
    public function HookActionCartSave($params){
        if(!empty($params['cart']->id)){
            $dealtCart= new DealtCart();
            $dealtCart->sanitizeDealtCart($params['cart']->id);
        }

    }
    public function HookActionCartUpdateQuantityBefore($params){
        if(!empty($params['cart']->id)){
            $dealtCart= new DealtCart();
            $dealtCart->sanitizeDealtCartQuantities($params);
        }

    }
    public function HookActionCarrierProcess($params){
        if(Tools::isSubmit('confirmDeliveryOption')){
            $dealtCheckoutValidation= new DealtCheckoutValidation($params['cart']);
            if(!$dealtCheckoutValidation->isValid()){
                $this->context->cookie->__set('redirect_errors', $this->l('Dealt Service is not available please try again'));
                Tools::redirect('index.php?controller=cart&action=show');
            }
        }
    }
    /**
     * @param $model
     * @return mixed
     */
    public function getBuilder($model)
    {
        $factory = new BuilderFactory($model);
        return $factory->getBuilderInstance();
    }



    /**
     * display dealt bloc
     */
    public function updateDealtBlock($params)
    {
        if (isset($params['product'])) {
            if ($params['product']['availability'] == 'unavailable') {
                return null;
            }

            $productId = (int)Tools::getValue('id_product');
            $productAttributeId = (int)Tools::getValue('id_product_attribute');
            $id_lang = \Context::getContext()->language->id ?? Configuration::get('PS_LANG_DEFAULT');
            $id_currency = \Context::getContext()->currency->id ?? Configuration::get('PS_CURRENCY_DEFAULT');
            $cart = $params['cart'];
            $data = DealtPresenter::present($cart, $productId, $productAttributeId, $id_lang, $id_currency);

            if ($data == null) {
                return null;
            }

            $this->smarty->assign($data);

            return $this->fetch(_DEALT_MODULE_TEMPLATES_DIR_ . 'hook/DisplayProductAdditionalInfo.tpl');
        }

        return null;

    }
    public function ajaxDisplayIdProductAttribute(){

    }

    public function ajaxCheckAvailability(){
        $id_offer=Tools::getValue('id_offer');
        $zip_code=Tools::getValue('zip_code');
        $result=$this->getClient()->checkAvailability($id_offer, $zip_code);
        die(json_encode($result));
    }
    public function ajaxAddToCart(){
        $dealCart= new DealtCart();
        $dealCart->addDealtOfferToCart(Tools::getValue('id_offer'), (int)Tools::getValue('id_product'), (int)Tools::getValue('id_product_attribute'));
        $this->returnResultToAjax();
    }
    /**
     * Transfers error messages to AJAX
     *
     * @param array $errors Error messages
     */
    public function returnResultToAjax($errors = [])
    {
        $result = [
            'errors' => $errors
        ];

        die(json_encode($result));
    }
    public function hookActionPaymentConfirmation($params)
    {
        $orderId = intval($params['id_order']);
        $this->getClient()->handleOrderPayment($orderId);
    }

    private function setEmployee()
    {
        $id_employee=\Db::getInstance()->getValue("SELECT id_employee FROM "._DB_PREFIX_."employee WHERE id_profile=1 ORDER BY id_employee ASC");
        $this->context->employee= new Employee($id_employee);

    }
}
