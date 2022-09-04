<?php


/**
 * Class AdminDealtModuleController
 */
class AdminDealtModuleController extends ModuleAdminController
{
    /**
     * AdminADealtModuleController constructor
     */
    public function __construct()
    {
        $this->table = 'Configuration';
        $this->class = 'Configuration';

        parent::__construct();
    }
}
