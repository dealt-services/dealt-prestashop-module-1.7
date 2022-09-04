<?php

include(dirname(__FILE__).'/../../config/config.inc.php');

$module_instance = Module::getInstanceByName('dealtmodule');

if (!$module_instance instanceof Dealtmodule) {
    exit;
}

if (Tools::getValue('advdealttoken') != sha1(_COOKIE_KEY_.$module_instance->name)) {
    exit;
}

$action = Tools::getValue('action');

switch ($action) {
    case 'update_dealt_block':
        $module_instance->ajaxUpdateDealtBlock();
        break;

    case 'check_offer_availability':
        $module_instance->ajaxCheckAvailability();
        break;

    case 'add_to_cart':
        $module_instance->ajaxAddToCart();
        break;
}
