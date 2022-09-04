<?php

namespace Dealt\Module\Dealtmodule\Api;
/**
 * Get the current environment used: prod or test // sandbox or live
 */
class DealtEnv
{
    /**
     * Environment name: can be 'prod' or 'test'
     *
     * @var string
     */
    protected $name;

    /**
     * Environment mode: can be 'live' or 'sandbox'
     *
     * @var string
     */
    protected $mode;
    /**
     * @var string
     */
    private $dealtApiKey;

    public function __construct()
    {
        if (true === $this->isLive()) {
            $this->setMode('live');
            $this->setName('test');
        } else {
            $this->setMode('sandbox');
            $this->setName('test');
        }
        $this->setDealtApiKey(\Configuration::get('DEALT_MODULE_API_KEY'));
    }

    /**
     * getter for DealtApiKey
     */
    public function getDealtApiKey()
    {
        return $this->dealtApiKey;
    }

    /**
     * setter for DealtApiKey
     *
     * @param string $apiKey
     */
    private function setDealtApiKey($apiKey)
    {
        $this->dealtApiKey = $apiKey;
    }

    /**
     * Check if the module is in SANDBOX or LIVE mode
     *
     * @return bool true if the module is in LIVE mode
     */
    private function isLive()
    {
        $mode = \Configuration::get('DEALT_MODULE_MODE');

        if ('1' === $mode) {
            return true;
        }

        return false;
    }

    /**
     * getter for name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * getter for mode
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * setter for name
     *
     * @param string $name
     */
    private function setName($name)
    {
        $this->name = $name;
    }

    /**
     * setter for mode
     *
     * @param string $mode
     */
    private function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return (bool) \Configuration::get('DEALT_MODULE_LOG');
    }
}
