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

    public function __construct(ReflectionMethod $method)
    {
        $this->method = $method;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s function %s(%s)%s',
            implode(' ', Reflection::getModifierNames($this->method->getModifiers())),
            $this->method->getShortName(),
            $this->renderParamSignature(),
            $this->renderReturnType()
        );
    }

    private function renderParamSignature(): string
    {
        $paramStrings = array_map(
            function (ReflectionParameter $param): string {
                $str = '';
                $type = $param->getType();
                $typeName = $this->ensureValidFQCN($type->getName());

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

    private function ensureValidFQCN(string $typeName): string
    {
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
            $this->ensureValidFQCN($type->getName())
        );
    }
}