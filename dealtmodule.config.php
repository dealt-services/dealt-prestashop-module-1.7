<?php

if (!defined('_DEALT_MODULE_DIR_')) {
    /** @var string Path to module directory */
    define('_DEALT_MODULE_DIR_', dirname(__FILE__).'/');
}

if (!defined('_DEALT_MODULE_URI_')) {
    /** @var string URL to module directory */
    define('_DEALT_MODULE_URI_', _MODULE_DIR_.'dealtmodule/');
}

if (!defined('_DEALT_MODULE_CLASSES_DIR_')) {
    /** @var string Path to module classes directory */
    define('_DEALT_MODULE_CLASSES_DIR_', _DEALT_MODULE_DIR_.'classes/');
}
if (!defined('_DEALT_MODULE_MODELS_DIR_')) {
    /** @var string Path to module Builder directory */
    define('_DEALT_MODULE_MODELS_DIR_', _DEALT_MODULE_DIR_.'Model/');
}
if (!defined('_DEALT_MODULE_API_DIR_')) {
    /** @var string Path to module api directory */
    define('_DEALT_MODULE_API_DIR_', _DEALT_MODULE_DIR_.'api/');
}
if (!defined('_DEALT_MODULE_BUILDERS_DIR_')) {
    /** @var string Path to module Builder directory */
    define('_DEALT_MODULE_BUILDERS_DIR_', _DEALT_MODULE_DIR_.'Builder/');
}
if (!defined('_DEALT_MODULE_CONTROLLERS_DIR_')) {
    /** @var string Path to module controllers directory */
    define('_DEALT_MODULE_CONTROLLERS_DIR_', _DEALT_MODULE_DIR_.'controllers/');
}

if (!defined('_DEALT_MODULE_CSS_URI_')) {
    /** @var string URL to module CSS files directory */
    define('_DEALT_MODULE_CSS_URI_', _DEALT_MODULE_URI_.'views/css/');
}

if (!defined('_DEALT_MODULE_JS_URI_')) {
    /** @var string URL to module JS files directory */
    define('_DEALT_MODULE_JS_URI_', _DEALT_MODULE_URI_.'views/js/');
}

if (!defined('_DEALT_MODULE_IMG_URI_')) {
    /** @var string URL to module images directory */
    define('_DEALT_MODULE_IMG_URI_', _DEALT_MODULE_URI_.'views/img/');
}
if (!defined('_DEALT_MODULE_MANUAL_URI_')) {
    /** @var string URL to module manual directory */
    define('_DEALT_MODULE_MANUAL_URI_', _DEALT_MODULE_URI_.'manual/');
}

if (!defined('_DEALT_MODULE_TEMPLATES_DIR_')) {
    /** @var string Path to module templates directory */
    define('_DEALT_MODULE_TEMPLATES_DIR_', _DEALT_MODULE_DIR_.'views/templates/');
}
if (!defined('_DEALT_MODULE_AJAX_URI_')) {
    /** @var string URL to module AJAX file */
    define('_DEALT_MODULE_AJAX_URI_', _DEALT_MODULE_URI_.'dealtmodule.ajax.php');
}
if (!defined('_DEALT_MODULE_LOG_DIR_')) {
    /** @var string Path to module logs directory */
    define('_DEALT_MODULE_LOG_DIR_', _DEALT_MODULE_DIR_.'log/');
}

if (!defined('_DEALT_MODULE_LOG_URI_')) {
    /** @var string URL to module logs directory */
    define('_DEALT_MODULE_LOG_URI_', _DEALT_MODULE_URI_.'log/');
}

if (!defined('_DEALT_MODULE_FRONT_TEMPLATE_DIR_')) {
    /** @var string URL to module logs directory */
    define('_DEALT_MODULE_FRONT_TEMPLATE_DIR_', 'module:dealtmodule/views/templates/front/');
}
