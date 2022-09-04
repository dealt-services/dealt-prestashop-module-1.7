<?php

use Dealt\Module\Dealtmodule\Utils\DealtTools;


/**
 * Class DealtModuleInstaller Responsible for module hooks registration, database tables installation
 * And default configuration setup
 */
class DealtModuleInstaller
{
    /**
     * Dealt tab controller name
     */
    const CONTROLLER_DEALT = 'AdminDealt';
    /**
     * Dealt modules tab controller name
     */
    const CONTROLLER_DEALT_MODULES = 'AdminDealtModules';
    /**
     * @var array Used to collect error messages during install / uninstall process
     */
    public $errors = [];

    /**
     * Module installation function
     * Collects error messages if installation is not successful
     *
     * @param Module $module Module object
     */
    public function install(Module $module)
    {
        Configuration::updateValue(DealtModuleLogger::FILENAME, Tools::passwdGen());

        if (!$this->registerHooks($module)) {
            $this->errors[] = $module->l('Could not register module hooks', __CLASS__);

            return;
        }

        if (!$this->installTabs($module)) {
            return;
        }

        if (!$this->installDatabase()) {
            $this->errors[] = $module->l('Could not install module database tables', __CLASS__);

            return;
        }

        if (!$this->saveConfiguration()) {
            $this->errors[] = $module->l('Could not save configuration', __CLASS__);
            return;
        }
        if (!DealtTools::createDealtCategory()) {
            $this->errors[] = $module->l('Could not create Dealt category', __CLASS__);
            return;
        }
    }

    /**
     * Module uninstall function
     * Collects error messages if uninstall is not successful
     *
     * @param Module $module Module object
     */
    public function uninstall(Module $module)
    {
        if (!$this->uninstallTabs($module)) {
            return;
        }

        if (!$this->uninstallDatabase()) {
            $this->errors[] = $module->l('Could not delete module database tables', __CLASS__);

            return;
        }

        if (!$this->deleteConfiguration()) {
            $this->errors[] = $module->l('Could not delete configuration', __CLASS__);

            return;
        }
        if (!DealtTools::deleteDealtCategory()) {
            $this->errors[] = $module->l('Could not delete Dealt category', __CLASS__);

            return;
        }

        if (!$this->deleteLogs($module)) {
            return;
        }
    }

    /**
     * Registers BackOffice tabs
     *
     * @param string $name Tab name
     * @param int $id_parent Parent tab ID
     * @param string $class_name Tab controller class name
     * @param string $moduleName Module name
     * @param $icon
     * @return bool|int Tab could not be installed | Installed tab ID
     */
    private function installTab($name, $id_parent, $class_name, $moduleName, $icon)
    {
        if (!Tab::getIdFromClassName($class_name)) {
            $module_tab = new Tab;
            $languages = Language::getLanguages(true);

            foreach ($languages as $language) {
                $module_tab->name[(int)$language['id_lang']] = $name;
            }

            $module_tab->class_name = $class_name;
            $module_tab->id_parent = (int)$id_parent;
            $module_tab->module = $moduleName;
            $module_tab->icon=$icon;
            if ($module_tab->add()) {
                return (int)$module_tab->id;
            }
        }

        return false;
    }

    /**
     * Removes BackOffice tab registration
     *
     * @param string $class_name Tab controller class name
     * @return bool Tab uninstalled successfully or not
     */
    private function uninstallTab($class_name)
    {
        if ($id_tab = (int)Tab::getIdFromClassName($class_name)) {
            $tab = new Tab((int)$id_tab);

            return $tab->delete();
        }

        return true;
    }

    /**
     * Returns module tabs data
     *
     * @param Module $module Module object
     * @return array Module tabs data
     */
    private function getTabs(Module $module)
    {
        return [
            // Dealt tabs begin
            [
                'name' => $module->l('Dealt', __CLASS__),
                'parent' => 0,
                'class_name' => self::CONTROLLER_DEALT,
                'icon' => 'settings',
                'module_tab' => true,
                'main_tab' => true
            ],
            [
                'name' => $module->l('Dealt service', __CLASS__),
                'parent' => self::CONTROLLER_DEALT,
                'class_name' => self::CONTROLLER_DEALT_MODULES,
                'icon' => 'settings',
                'module_tab' => false,
                'modules_tab' => true
            ],
            [
                'name' => $module->l('Offers', __CLASS__),
                'parent' => self::CONTROLLER_DEALT_MODULES,
                'class_name' => $module::CONTROLLER_DEALS,
                'module_tab' => true
            ],
            [
                'name' => $module->l('Missions', __CLASS__),
                'parent' => self::CONTROLLER_DEALT_MODULES,
                'class_name' => $module::CONTROLLER_MISSIONS,
                'module_tab' => true
            ],
            [
                'name' => $module->l('Settings', __CLASS__),
                'parent' => self::CONTROLLER_DEALT_MODULES,
                'class_name' => $module::CONTROLLER_CONFIGURATION,
                'module_tab' => true
            ],
            [
                'name' => $module->l('Info', __CLASS__),
                'parent' => self::CONTROLLER_DEALT_MODULES,
                'class_name' => $module::CONTROLLER_INFO,
                'module_tab' => true
            ]
        ];
    }

    /**
     * Function used to install module tabs
     * Collects error messages if install process is not successful
     *
     * @param Module $module Module object
     * @return bool Tabs installed successfully or not
     */
    private function installTabs(Module $module)
    {
        $tabs = $this->getTabs($module);

        if (empty($tabs)) {
            return true;
        }

        foreach ($tabs as $tab) {
            if (Tab::getIdFromClassName($tab['class_name'])) {
                continue;
            }

            $id_parent = is_int($tab['parent']) ? (int)$tab['parent'] : (int)Tab::getIdFromClassName($tab['parent']);
             $icon=$tab['icon'] ?? null;
            if (!$this->installTab($tab['name'], (int)$id_parent, $tab['class_name'], $module->name, $icon)) {
                $this->errors[] = sprintf($module->l('Could not install %s tab', __CLASS__), $tab['name']);

                return false;
            }
        }

        return true;
    }

    /**
     * Function used to uninstall module tabs
     * Collects error messages if uninstall process is not successful
     *
     * @param Module $module Module object
     * @return bool Tabs uninstalled successfully or not
     */
    private function uninstallTabs(Module $module)
    {
        $tabs = $this->getTabs($module);

        if (empty($tabs)) {
            return true;
        }

        $parent_tabs = [];
        $modules_tab = [];

        foreach ($tabs as $tab) {
            if (!$tab['module_tab']) {
                if (isset($tab['modules_tab'])) {
                    $modules_tab = $tab; // Dealt Modules tab
                    $parent_tabs[] = $tab;
                } else {
                    $parent_tabs[] = $tab;
                }

                continue; // Dealt tabs will be uninstalled separately if needed
            }

            if (!$this->uninstallTab($tab['class_name'])) {
                $this->errors[] = sprintf($module->l('Could not uninstall %s tab', __CLASS__), $tab['name']);

                return false;
            }
        }

        if (!empty($modules_tab)) {
            if (Tab::getNbTabs(Tab::getIdFromClassName($modules_tab['class_name']))) {
                return true; // Dealt modules tab is not empty
            }

            foreach ($parent_tabs as $tab) {
                if (!$this->uninstallTab($tab['class_name'])) {
                    $this->errors[] = sprintf($module->l('Could not uninstall %s tab', __CLASS__), $tab['name']);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns database tables installation queries
     *
     * @return array Database tables installation queries
     */
    private function getInstallQueries()
    {
        $sql = [];

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_offer` (
                `id_offer` int(11) NOT NULL AUTO_INCREMENT,
                `dealt_id_offer` VARCHAR(255) NOT NULL,
                `id_dealt_product` INT(11) NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_offer`),
            UNIQUE (dealt_id_offer)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_offer_shop` (
                `id_offer` int(11) NOT NULL AUTO_INCREMENT,
                `id_shop` INT(11) NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_offer`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_offer_lang` (
                `id_offer` int(11) NOT NULL AUTO_INCREMENT,
                `id_shop` INT(11) NOT NULL,
                `id_lang` INT(11) NOT NULL,
                `title_offer` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_offer`, `id_shop`, `id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_mission` (
                `id_mission` int(11) NOT NULL AUTO_INCREMENT,
                `id_offer` int(11) NOT NULL,
                `id_shop_group` int(11) NOT NULL,
                `id_shop` int(11) NOT NULL,
                `id_product` int(11) NOT NULL,
                `id_product_attribute` int(11) NOT NULL,
                `id_dealt_product` int(11) NOT NULL,
                `id_order` int(11) NOT NULL,
                `dealt_id_mission` VARCHAR(255) NOT NULL,
                `dealt_status_mission` VARCHAR(255) NOT NULL,
                `dealt_gross_price_mission` decimal(20,6) NOT NULL,
                `dealt_vat_price_mission` decimal(20,6) NOT NULL,
                `dealt_net_price_mission` decimal(20,6) NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_mission`, `id_offer`, `id_shop`, `id_product`, `id_product_attribute`, `id_dealt_product`, `id_order`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_offer_category` (
                `id_dealt_offer_category` int(11) NOT NULL AUTO_INCREMENT,
                `id_offer` int(11) NOT NULL,
                `id_dealt_product` int(11) NOT NULL,
                `id_category` int(11) NOT NULL,
            PRIMARY KEY (`id_dealt_offer_category`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dealt_cart_product_ref` (
                `id_dealt_cart_product_ref` int(11) NOT NULL AUTO_INCREMENT,
                `id_cart` int(11) NOT NULL,
                `id_product` int(11) NOT NULL,
                `id_product_attribute` int(11) NOT NULL,
                `id_offer` int(11) NOT NULL,
            PRIMARY KEY (`id_dealt_cart_product_ref`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        return $sql;
    }

    /**
     * Returns database tables uninstall queries
     *
     * @return array Database tables uninstall queries
     */
    private function getUninstallQueries()
    {
        $sql = [];
        $dealProducts=\Db::getInstance()->executeS(
            "SELECT id_dealt_product FROM `" . _DB_PREFIX_ . "dealt_offer`"
        );
        if(!empty($dealProducts)){
            foreach ($dealProducts as $item){
                $id_product=$item['id_dealt_product'];
                $product= new Product($id_product);
                $product->delete();
            }
        }

        $sql[] = '
            DROP TABLE IF EXISTS
                `' . _DB_PREFIX_ . 'dealt_offer`,
                `' . _DB_PREFIX_ . 'dealt_offer_shop`,
                `' . _DB_PREFIX_ . 'dealt_offer_lang`,
                `' . _DB_PREFIX_ . 'dealt_mission`,
                `' . _DB_PREFIX_ . 'dealt_offer_category`,
                `' . _DB_PREFIX_ . 'dealt_cart_product_ref`';

        return $sql;
    }

    /**
     * Registers module hooks
     *
     * @param Module $module Module object
     * @return bool Module hooks registered successfully
     */
    private function registerHooks(Module $module)
    {
        return
            $module->registerHook('displayHeader') &&
            $module->registerHook('displayBackOfficeHeader') &&
            version_compare(_PS_VERSION_, '1.7.1', '<') ? $module->registerHook('displayProductExtraContent') : $module->registerHook('displayProductAdditionalInfo') &&
            $module->registerHook('actionCartSave') &&
            $module->registerHook('actionAdminDealtModuleDealsFormModifier') &&
            $module->registerHook('actionCarrierProcess') &&
            $module->registerHook('actionFrontControllerSetMedia') &&
            $module->registerHook('actionCartUpdateQuantityBefore') &&
            $module->registerHook('actionPaymentConfirmation');
    }

    /**
     * Takes database tables install queries and executes them
     *
     * @return bool Database tables installed successfully or not
     */
    private function installDatabase()
    {
        $sql = $this->getInstallQueries();

        if (empty($sql)) {
            return true;
        }

        foreach ($sql as $query) {
            if (\Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Takes database tables uninstall queries and executes them
     *
     * @return bool Database tables uninstalled successfully or not
     */
    private function uninstallDatabase()
    {
        $sql = $this->getUninstallQueries();

        if (empty($sql)) {
            return true;
        }

        foreach ($sql as $query) {
            if (\Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns default configuration data
     *
     * @return array Default configuration data
     */
    private function getDefaultConfiguration()
    {
        return [
            'DEALT_MODULE_LOG' => 1,
            'DEALT_MODULE_API_KEY' => null,
            'DEALT_MODULE_PRODUCT_CATEGORY' => null,
            'DEALT_MODULE_MODE' => 0
        ];
    }

    /**
     * Takes default configuration and saves it to database
     *
     * @return bool Configuration saved successfully or not
     */
    private function saveConfiguration()
    {
        $configuration = $this->getDefaultConfiguration();

        foreach ($configuration as $setting => $value) {
            if (!Configuration::updateValue($setting, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes configuration data from database
     *
     * @return bool Configuration removed successfully or not
     */
    private function deleteConfiguration()
    {
        $configuration = $this->getDefaultConfiguration();

        foreach (array_keys($configuration) as $setting) {
            if (!Configuration::deleteByName($setting)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes module logs file from module logs directory
     * Collects error messages if logs deletion process is not successful
     *
     * @param Module $module Module object
     * @return bool Module logs file removed successfully
     */
    private function deleteLogs(Module $module)
    {
        $filename = Configuration::get(DealtModuleLogger::FILENAME);
        $file_path = _DEALT_MODULE_LOG_DIR_ . $filename . DealtModuleLogger::FILENAME_EXTENSION;

        if (file_exists($file_path)) {
            try {
                if (!unlink($file_path)) {
                    $this->errors[] = $module->l('Could not delete logs file', __CLASS__);

                    return false;
                }
            } catch (Exception $e) {
                $this->errors[] = sprintf($module->l('Could not delete logs file: %s', __CLASS__), $e->getMessage());

                return false;
            }
        }

        if (!Configuration::deleteByName(DealtModuleLogger::FILENAME)) {
            return false;
        }

        return true;
    }


}
