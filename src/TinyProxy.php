<?php

namespace Noem\TinyProxy;

use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionMethod;

class TinyProxy
{
    private const CLASS_NAME_SUFFIX = '_TinyProxy';

    /**
     * @throws \ReflectionException
     */
    public static function generateCode(string $classFQCN): string
    {
        $reflectionClass = new ReflectionClass($classFQCN);
        $methods = array_map(
            function (string $name) use ($classFQCN) {
                if ($name === '__construct') {
                    return '';
                }

                return sprintf(
                    <<<'PHPCODE'
        %s{
            return $this->ensureInstance()->%s(...func_get_args());
        }
    PHPCODE,
                    new MethodSignature(new ReflectionMethod($classFQCN, $name)),
                    $name
                );
            },
            get_class_methods($classFQCN)
        );
        return sprintf(
            <<<'PHPCODE'
%s

class %s extends \%s {

%s

%s

%s

}
PHPCODE,
            self::renderNamespace($reflectionClass),
            self::proxyClassName($classFQCN, true),
            $classFQCN,
            self::renderConstructor($reflectionClass),
            self::renderMethods(),
            implode(PHP_EOL, $methods)
        );
    }

    private static function renderMethods(): string
    {
        return <<<'PHPCODE'

    public function __call(string $name , array $arguments){
    
        return $this->ensureInstance()->$name(...$arguments);
    }
    
    public function __get( $key){
        return $this->ensureInstance()->$key;
    }
    
    private function ensureInstance(){
    
        if(!$this->instance){
            $this->instance = ($this->factory)(); 
        }
    
        return $this->instance;
    }

PHPCODE;
    }

    #[Pure] private static function renderConstructor(ReflectionClass $reflectionClass): string
    {
        $properties = $reflectionClass->getProperties();
        /**
         * calling unset() on all properties prevents errors from uninitialized typed properties
         */
        $unset = [];
        foreach ($properties as $property) {
            $unset[] = sprintf(
                <<<'PHPCODE'
        unset($this->%s);
PHPCODE
                ,
                $property->getName()
            );
        }

        return sprintf(
            <<<'PHPCODE'
    private $factory;
    
    private $instance;
    
    public function __construct(callable $factory){
        $this->factory=$factory;
%s
    }
PHPCODE
            ,
            implode(PHP_EOL, $unset)
        );
    }

    #[Pure] private static function renderNamespace(ReflectionClass $reflectionClass): string
    {
        return sprintf('namespace %s;%s', $reflectionClass->getNamespaceName(), PHP_EOL);
    }

    public static function proxyClassName(
        string $subjectFQCN,
        bool $short = false
    ): string {
        $subjectFQCN = !$short ? $subjectFQCN : (new ReflectionClass($subjectFQCN))->getShortName();
        return sprintf('%s%s', $subjectFQCN, self::CLASS_NAME_SUFFIX);
    }

    /**
     * Checks if the given service name is the FQCN of an existing class.
     * If that is not the case, attempt to determine the FQCN from the signature of the factory function.
     * This is useful for DI Containers that use arbitrary strings as service IDs
     *
     * @param string $serviceName
     * @param callable $factory
     * @return string
     * @throws \ReflectionException
     */
    public static function subjectClassName(string $serviceName, callable $factory): string
    {
        if (class_exists($serviceName)) {
            return $serviceName;
        }
        $refFunc = new \ReflectionFunction($factory);
        $returnType = $refFunc->getReturnType();
        if (!$returnType) {
            throw new \InvalidArgumentException('could not determine target class name');
        }
        $returnType = $returnType->getName();
        if (!class_exists($returnType)) {
            throw new \InvalidArgumentException('could not determine target class name');
        }
        return $returnType;
    }
}
