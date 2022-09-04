<?php

declare(strict_types=1);
namespace Dealt\Module\Dealtmodule\Builder;

use LogicException;

class BuilderFactory
{
    private $class;

    public function __construct($class)
    {
        $this->class=$class;
    }

    /**
     * @param $class
     * @return mixed
     */
    public function getBuilderInstance()
    {
        $class_name = ucfirst($this->class) . 'Builder';
        $classNamespace = '\\Dealt\\Module\\Dealtmodule\\Builder\\' . $class_name;

        if (!class_exists($classNamespace)) {
            throw new LogicException("Unable to load class: $class_name");
        }

        return new $classNamespace();
    }
}