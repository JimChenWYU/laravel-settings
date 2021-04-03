<?php

namespace Spatie\LaravelSettings\Support;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelSettings\Exceptions\CouldNotResolveDocblockType;

class PropertyReflector
{
    public static function resolveType(
        ReflectionProperty $reflectionProperty
    ): ?Type {
        if (\PHP_VERSION_ID >= 70400) {
            $reflectionType = $reflectionProperty->getType();
        } else {
            $reflectionType = null;
        }
        $docblock = $reflectionProperty->getDocComment();

        if ($reflectionType === null && empty($docblock)) {
            return null;
        }

        if ($docblock) {
            preg_match('/@var ((?:(?:[\w?|\\\\<>,\s])+(?:\[])?)+)/', $docblock, $output_array);

            $types = collect(explode('|', $output_array[1]))->map(function ($v) {
                return trim($v);
            });
            if ($types->intersect([
                'mixed',
                'int', 'integer',
                'float', 'double',
                'bool', 'boolean',
                'string',
                'array',
                'iterable', 'Iterator',
            ])->isNotEmpty()) {
                return null;
            }
            if ($types->count() === 1) {
                if (class_exists($types->first()) || interface_exists($types->first())) {
                    $type = new Object_(new Fqsen('\\' . ltrim($types->first(), '\\')));
                    return $type;
                }
            }

            if ($types->count() === 2 && $types->contains('null')) {
                $type = new Object_(new Fqsen('\\' . ltrim($types->first(), '\\')));
                return new Nullable($type);
            }

            return count($output_array) === 2
                ? self::reflectDocblock($reflectionProperty, $output_array[1])
                : null;
        }

        if ($reflectionType->isBuiltin()) {
            return null;
        }

        if (! $reflectionType instanceof ReflectionNamedType) {
            return null;
        }

        $type = new Object_(new Fqsen('\\' . $reflectionType->getName()));

        return $reflectionType->allowsNull()
            ? new Nullable($type)
            : $type;
    }

    protected static function reflectDocblock(
        ReflectionProperty $reflectionProperty,
        string $type
    ): Type {
        $resolvedType = (new TypeResolver())->resolve($type);

        if ($resolvedType instanceof Nullable) {
            return new Nullable(self::reflectDocblock(
                $reflectionProperty,
                (string) $resolvedType->getActualType()
            ));
        }

        if ($resolvedType instanceof Object_) {
            return $resolvedType;
        }

        if ($resolvedType instanceof AbstractList) {
            $isValid = $resolvedType->getValueType() instanceof Boolean
                || $resolvedType->getValueType() instanceof Array_
                || $resolvedType->getValueType() instanceof Float_
                || $resolvedType->getValueType() instanceof Integer
                || $resolvedType->getValueType() instanceof String_
                || $resolvedType->getValueType() instanceof Object_;

            if ($isValid) {
                return $resolvedType;
            }
        }

        throw CouldNotResolveDocblockType::create($type, $reflectionProperty);
    }
}
