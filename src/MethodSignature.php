<?php

declare(strict_types=1);

namespace Noem\TinyProxy;

use Reflection;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class MethodSignature
{

    /**
     * @var ReflectionMethod
     */
    private $method;

    public function __construct(\ReflectionFunctionAbstract $method)
    {
        $this->method = $method;
    }

    public function __toString(): string
    {
        $function = sprintf(
            'function %s(%s)%s',
            $this->method->getShortName(),
            $this->renderParamSignature(),
            $this->renderReturnType()
        );
        if ($this->method instanceof ReflectionMethod) {
            $modifiers = implode(' ', Reflection::getModifierNames($this->method->getModifiers()));

            $function = $modifiers . ' ' . $function;
        }
        return $function;
    }

    private function renderParamSignature(): string
    {
        $paramStrings = array_map(
            function (ReflectionParameter $param): string {
                $str = '';
                $type = $param->getType();
                $typeName = $this->ensureValidParamTypeString($type);

                if (
                    $type
                    && $type instanceof ReflectionNamedType
                    && !$param->isVariadic()
                ) {
                    $nullable = $type->allowsNull()
                        ? '?'
                        : '';
                    $str .= $nullable . $typeName . ' ';
                }

                if ($param->isVariadic()) {
                    $str .= '...';
                }

                $str .= '$' . $param->getName();
                if ($param->isOptional() && !$param->isVariadic()) {
                    $str .= ' = null';
                }

                return $str;
            },
            $this->method->getParameters()
        );

        return implode(', ', $paramStrings);
    }

    private function ensureValidParamTypeString(\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map([$this, 'ensureValidParamTypeString'], $type->getTypes()));
        }
        assert($type instanceof ReflectionNamedType);
        $typeName = $type->getName();
        if (class_exists($typeName) || interface_exists($typeName)) {
            $typeName = '\\' . $typeName;
        }
        return $typeName;
    }

    private function renderReturnType(): string
    {
        $type = $this->method->getReturnType();
        if (!$type || !$type instanceof ReflectionNamedType) {
            return '';
        }

        return sprintf(
            ': %s',
            $this->ensureValidParamTypeString($type)
        );
    }
}
