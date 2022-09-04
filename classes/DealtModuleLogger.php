<?php

/**
 * Class DealtModuleLogger Responsible for module logs management
 */
class DealtModuleLogger
{
    /**
     * Log type when process was successful
     */
    const TYPE_SUCCESS = 'SUCCESS';

    /**
     * Log type when process was not successful
     */
    const TYPE_ERROR = 'ERROR';

    /**
     * Log type when process has warning info
     */
    const TYPE_WARNING = 'WARNING';

    /**
     * Log type when process has extra info
     */
    const TYPE_INFO = 'INFO';

    /**
     * Color used for info type log
     */
    const INFO_COLOR = '#0000FF';

    /**
     * Color used for success type log
     */
    const SUCCESS_COLOR = '#006400';

    /**
     * Color used for error type log
     */
    const ERROR_COLOR = '#FF0000';

    /**
     * Color used for warning type log
     */
    const WARNING_COLOR = '#FF8C00';

    /**
     * Logs file configuration name in database configuration table
     */
    const FILENAME = 'DEALT_MODULE_LOG_FILENAME';

    /**
     * Logs file extension
     */
    const FILENAME_EXTENSION = '.html';

    /**
     * Type of writing to file / reading from file
     */
    const FILE_OPEN_TYPE = 'a';

    /**
     * Current file name
     */
    const CURRENT_FILENAME = 'DealtModuleLogger';

    /**
     * @var string Logs file name
     */
    private $filename;

    /**
     * @var  Current class instance
     */
    private static $instance;

    /**
     * DealtModuleLogger constructor
     */
    public function __construct()
    {
        if (!$this->filename) {
            $this->filename = $this->getFilePath();
        }
    }

    /**
     * Creates log
     *
     * @param string $content Log message
     * @param string $type Log type
     * @param array $details Log extra info
     */
    public static function log($content, $type = self::TYPE_INFO, $details = [])
    {
        if (!Configuration::get('DEALT_MODULE_LOG')) {
            return;
        }

        $logger = self::getInstance();
        $content = $logger->formatContent($content, $type, $details);
        $logger->writeToFile($content);
    }

    /**
     * Formats and returns logs file path
     *
     * @return string Logs file path
     */
    private function getFilePath()
    {
        $logFilename = Configuration::get(self::FILENAME);

        if (!$logFilename) {
            $logFilename = Tools::passwdGen();
            Configuration::updateValue(self::FILENAME, $logFilename);
        }

        return _DEALT_MODULE_LOG_DIR_.$logFilename.self::FILENAME_EXTENSION;
    }

    /**
     * Writes log into log file
     *
     * @param string $content Log content
     */
    private function writeToFile($content)
    {
        $handle = fopen($this->filename, self::FILE_OPEN_TYPE);
        fwrite($handle, $content);
        fclose($handle);
    }

    /**
     * Prepares log content to write it into file
     *
     * @param string $content Log content
     * @param string $type Log type
     * @param array $details Log extra info
     * @return string Log in HTML format
     * @throws Exception
     * @throws SmartyException
     */
    private function formatContent($content, $type, array $details)
    {

        $this->setLogParameters($type);
        $this->setContentParameters($content, $details);
        return \Context::getContext()->smarty->fetch(_DEALT_MODULE_TEMPLATES_DIR_.'admin/log.tpl');
    }

    /**
     * Assigns log info into smarty
     *
     * @param string $content Log content
     * @param array $details Log extra info
     */
    private function setContentParameters($content, array $details)
    {
        \Context::getContext()->smarty->assign([
            'dealt_module_log_date' => date('Y-m-d H:i:s'),
            'dealt_module_log_content' => $content,
            'dealt_module_log_details' => $details
        ]);
    }

    /**
     * Sets log color and name according to type
     *
     * @param string $type Log type
     */
    private function setLogParameters($type)
    {
        $translatable_message = $this->getTranslatableLogType($type);

        switch ($type) {
            default:
            case self::TYPE_INFO:
                $this->addLogParametersToSmarty(self::INFO_COLOR, $translatable_message);
                break;
            case self::TYPE_SUCCESS:
                $this->addLogParametersToSmarty(self::SUCCESS_COLOR, $translatable_message);
                break;
            case self::TYPE_ERROR:
                $this->addLogParametersToSmarty(self::ERROR_COLOR, $translatable_message);
                break;
            case self::TYPE_WARNING:
                $this->addLogParametersToSmarty(self::WARNING_COLOR, $translatable_message);
                break;
        }
    }

    /**
     * Returns log name according to type
     *
     * @param string $type Log type
     * @return string Log name
     */
    private function getTranslatableLogType($type)
    {
        switch ($type) {
            default:
            case self::TYPE_INFO:
                return 'INFO';
            case self::TYPE_SUCCESS:
                return 'SUCCESS';
            case self::TYPE_ERROR:
                return 'ERROR';
            case self::TYPE_WARNING:
                return 'WARNING';
        }
    }

    /**
     * Assigns log additional details to smarty
     *
     * @param string $color Log color HTML code
     * @param string $translatable_type Log name
     */
    private function addLogParametersToSmarty($color, $translatable_type)
    {
        \Context::getContext()->smarty->assign([
            'dealt_module_log_color' => $color,
            'dealt_module_log_type' => $translatable_type
        ]);
    }

    /**
     * Creates and returns module instance
     *
     * @return DealtModuleLogger|Current Module instance
     */
    private static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DealtModuleLogger();
        }

        return self::$instance;
    }
}
