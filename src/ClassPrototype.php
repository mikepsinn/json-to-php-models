<?php

namespace Strikebit\Util;

class ClassPrototype implements ClassPrototypeInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $methods = [];

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
     * ClassPrototype constructor.
     *
     * @param string      $className
     * @param bool        $useTypeHinting
     * @param bool        $useFluentSetters
     * @param string|null $namespace
     */
    public function __construct(
        string $className,
        bool $useTypeHinting,
        bool $useFluentSetters = false,
        ?string $namespace = null,
        bool $excludeGettersAndSetters = false
    ) {
        $this->className = $className;
        $this->useTypeHinting = $useTypeHinting;
        $this->useFluentSetters = $useFluentSetters;
        $this->namespace = $namespace;
        $this->excludeGettersAndSetters = $excludeGettersAndSetters;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * {@inheritDoc}
     */
    public function addMethod(string $methodName, string $dataType, $example): void
    {
        if($dataType === "boolean"){$example = null;}
        if(is_object($example)){$example = null;}
        if(is_array($example)){$example = null;}
        if (!$this->hasMethod($methodName)) {
            $this->methods[] = [
                'methodName' => $methodName,
                'dataType' => $dataType,
                'example' => $example
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod(string $methodName): bool
    {
        foreach ($this->methods as $method) {
            if ($method['methodName'] === $methodName) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function printProperty(array $method): string
    {
        $propertyName = lcfirst(StringUtil::snakeKebabToCamelCase($method['methodName']));
        $dataType = $this->getDataType($method['dataType']);
        $ex = '';
        if(!empty($method['example'])){
            $ex = '
     * @example '.$method['example'];
        }
        return <<<EOL
    /**
     * @var $dataType$ex
     */
    public \$$propertyName;

EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function printMethod(array $method): string
    {
        $methodName = StringUtil::snakeKebabToCamelCase($method['methodName']);
        $propertyName = lcfirst($methodName);
        $dataType = $this->getDataType($method['dataType']);
        $getterPrefix = $this->getGetterPrefix($method['dataType']);

        if ($this->useTypeHinting && $this->useFluentSetters) {
            return $this->printPhp7FluentSettersMethod($dataType, $getterPrefix,
                $methodName, $propertyName, $method['example'] ?? null);
        } elseif ($this->useTypeHinting && !$this->useFluentSetters) {
            return $this->printPhp7Method($dataType, $getterPrefix, $methodName,
                $propertyName, $method['example'] ?? null);
        } elseif (!$this->useTypeHinting && $this->useFluentSetters) {
            return $this->printPhp5FluentSettersMethod($dataType, $getterPrefix, $methodName, $propertyName);
        } else {
            return $this->printPhp5Method($dataType, $getterPrefix, $methodName, $propertyName);
        }
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    protected function printPhp7Method(
        string $dataType,
        string $getterPrefix,
        string $methodName,
        string $propertyName,
        $example
    ): string {
        $ex = '';
        if(!empty($example)){
            $ex = '
     * @example '.$example;
        }
        return <<<EOL

    /**
     * @return $dataType|null$ex
     */
    public function $getterPrefix$methodName(): ?$dataType
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     */
    public function set$methodName(?$dataType \$$propertyName): void
    {
        \$this->$propertyName = \$$propertyName;
    }

EOL;
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    protected function printPhp7FluentSettersMethod(
        string $dataType,
        string $getterPrefix,
        string $methodName,
        string $propertyName,
        $example
    ): string {
        $ex = '';
        if(!empty($example)){
            $ex = '
     * @example '.$example;
        }
        return <<<EOL

    /**
     * @return $dataType|null$ex
     */
    public function $getterPrefix$methodName(): ?$dataType
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName$ex
     *
     * @return $this->className
     */
    public function set$methodName(?$dataType \$$propertyName): $this->className
    {
        \$this->$propertyName = \$$propertyName;
        
        return \$this;
    }

EOL;
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    protected function printPhp5Method(
        string $dataType,
        string $getterPrefix,
        string $methodName,
        string $propertyName
    ): string {
        return <<<EOL

    /**
     * @return $dataType|null
     */
    public function $getterPrefix$methodName()
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     */
    public function set$methodName(\$$propertyName)
    {
        \$this->$propertyName = \$$propertyName;
    }

EOL;
    }

    /**
     * @param string $dataType
     * @param string $getterPrefix
     * @param string $methodName
     * @param string $propertyName
     *
     * @return string
     */
    protected function printPhp5FluentSettersMethod(
        string $dataType,
        string $getterPrefix,
        string $methodName,
        string $propertyName
    ): string {
        return <<<EOL

    /**
     * @return $dataType|null
     */
    public function $getterPrefix$methodName()
    {
        return \$this->$propertyName;
    }

    /**
     * @param $dataType|null \$$propertyName
     *
     * @return $this->className
     */
    public function set$methodName(\$$propertyName)
    {
        \$this->$propertyName = \$$propertyName;
        
        return \$this;
    }

EOL;
    }

    /**
     * {@inheritDoc}
     */
    private function getDataType(string $dataType): string
    {
        switch ($dataType) {
            case 'integer':
                return 'int';
            case 'boolean':
                return 'bool';
            case 'double':
                return 'float';
            case 'NULL':
                return 'string';
            case 'Array':
                return 'array';
        }

        return $dataType;
    }

    /**
     * Get getter prefix
     *
     * @param string $dataType
     *
     * @return string
     */
    private function getGetterPrefix(string $dataType): string
    {
        return 'boolean' === $dataType ? 'is' : 'get';
    }

    public function __toString()
    {
        $str = <<<EOL
<?php


EOL;

        if ($this->namespace) {
            $str .= <<<EOL
namespace $this->namespace;


EOL;
        }

        $str .= <<<EOL
class $this->className extends \Quantimodo\Api\Model\DataSource\Connectors\ResponseObjects\BaseResponseObject
{

EOL
        ;
        foreach ($this->methods as $method) {
            $str .= $this->printProperty($method);
        }

        foreach ($this->methods as $method) {
            if(!$this->excludeGettersAndSetters){
                $str .= $this->printMethod($method);
            }
        }

        $str .= <<<EOL
}


EOL;


        return $str;
    }
}
