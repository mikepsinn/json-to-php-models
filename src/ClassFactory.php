<?php

namespace Strikebit\Util;

class ClassFactory implements ClassFactoryInterface
{
    /**
     * @var bool
     */
    private $useTypeHinting;

    /**
     * @var bool
     */
    private $useFluentSetters;

    /**
     * @var string|null
     */
    private $namespace;
    
    /**
     * @var bool
     */
    private $excludeGettersAndSetters;

    /**
     * ClassFactory constructor.
     *
     * @param bool        $useTypeHinting
     * @param bool        $useFluentSetters
     * @param string|null $namespace
     */
    public function __construct(bool $useTypeHinting, bool $useFluentSetters = false, ?string $namespace = null,
                                bool $excludeGettersAndSetters = false)
    {
        $this->useTypeHinting = $useTypeHinting;
        $this->useFluentSetters = $useFluentSetters;
        $this->namespace = $namespace;
        $this->excludeGettersAndSetters = $excludeGettersAndSetters;
    }

    /**
     * @param string      $className
     * @param \stdClass   $obj
     * @param array       $objects
     *
     * @return array
     */
    public function create(string $className, \stdClass $obj, array $objects = []): array
    {
        $newClass = new ClassPrototype($className, $this->useTypeHinting, 
            $this->useFluentSetters, $this->namespace, 
            $this->excludeGettersAndSetters);

        foreach ($obj as $key => $value) {
            if (is_object($value)) {
                $subClassName = StringUtil::snakeKebabToCamelCase($key);
                $newClass->addMethod($key, $subClassName, $value);
                $objects = $this->create($subClassName, $value, $objects);
            } elseif (is_array($value)) {
                $newClass->addMethod($key, gettype($value), $value);
                if (!empty($value) && is_object($value[0])) {
                    $subClassName = StringUtil::snakeKebabToCamelCase($key);
                    $objects = $this->create($subClassName, $value[0], $objects);
                }
            } else {
                $newClass->addMethod($key, gettype($value), $value);
            }
        }

        $objects[$className] = $newClass;

        return $objects;
    }
}
