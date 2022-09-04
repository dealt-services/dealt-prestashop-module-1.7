<?php

/**
 * Class AdminDealtModuleConfigurationController Responsible for module settings management
 */
class AdminDealtModuleConfigurationController extends ModuleAdminController
{
    /**
     * AdminDealtModuleConfigurationController constructor
     */
    public function __construct()
    {
        $this->table = 'configuration';
        $this->className = 'Configuration';
        $this->bootstrap = true;

        parent::__construct();

        $this->initOptions();
    }
    /**
     * Loads CSS / JS files
     *
     * @param bool $isNewTheme It is new theme or not
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS(_DEALT_MODULE_JS_URI_.'config.js');
    }
    /**
     * Updates module settings
     *
     * @throws PrestaShopException
     */
    public function processUpdateOptions()
    {
        $settings_keys = self::getDefaultSettings(true);
        $settings = Configuration::getMultiple($settings_keys);

        parent::processUpdateOptions();

        $updated_settings = Configuration::getMultiple($settings_keys);
        $difference = self::getDifferences($settings, $updated_settings);

        if ($difference) {
            DealtModuleLogger::log(
                'BO settings update',
                DealtModuleLogger::TYPE_INFO,
                $difference
            );
        }
    }

    /**
     * Collects array with default configuration names and their values
     *
     * @param bool $keys_only To get only configuration names without values or not
     * @return array Default settings names and their values
     */
    public static function getDefaultSettings($keys_only = false)
    {
        $settings = [
            'DEALT_MODULE_LOG' => 1,
            'DEALT_MODULE_API_KEY' => null,
            'DEALT_MODULE_MODE' => 0
        ];

        if ($keys_only) {
            return array_keys($settings);
        }

        return $settings;
    }

    /**
     * Collects module options data
     */
    private function initOptions()
    {
        $this->fields_options = [
            'general' => [
                'title' => $this->l('Main settings'),
                'fields' => [
                    'DEALT_MODULE_MODE' => [
                        'title' => $this->l('Dealt module process mode'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool',
                        'desc' => $this->l('Switch prod in production environment')
                    ],
                    'DEALT_MODULE_API_KEY' => [
                        'title' => $this->l('Api key'),
                        'type' => 'text',
                        'desc' => $this->l('Sdk deal Api key')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ],
            'log' => [
                'title' => $this->l('Log settings'),
                'description' => $this->displayLogsLink(),
                'fields' => [
                    'DEALT_MODULE_LOG' => [
                        'title' => $this->l('Enable dealt logs'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ],
            ]
        ];
    }


    /**
     * Prepares logs link to display in settings pagew
     *
     * @return string Logs link in HTML format
     * @throws Exception
     * @throws SmartyException
     */
    private function displayLogsLink()
    {
        $filename = Configuration::get(DealtModuleLogger::FILENAME);
        $logs_file_url = _PS_BASE_URL_._DEALT_MODULE_LOG_URI_.$filename.DealtModuleLogger::FILENAME_EXTENSION;
        $this->context->smarty->assign('logs_url', $logs_file_url);
        $log_link = $this->context->smarty->fetch(_DEALT_MODULE_TEMPLATES_DIR_.'admin/log_url.tpl');

        return sprintf($this->l('Logs file: %s'), $log_link);
    }

    /**
     * Returns difference between two arrays
     *
     * @param array $array1 First array
     * @param array $array2 Second array
     * @return array Arrays difference
     */
    private static function getDifferences(array $array1, array $array2)
    {
        $result = [];

        foreach ($array1 as $key => $value) {
            if (!isset($array2[$key]) || $value != $array2[$key]) {
                $result[$key] = json_encode([
                    'old_value' => $value,
                    'new_value' => $array2[$key]
                ]);
            }
        }

        return $result;
    }
}
