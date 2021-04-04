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
use phpDocumentor\Reflection\Types\Mixed_;
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

            if (count($output_array) === 2) {
                $reflectionType = self::reflectDocblock($reflectionProperty, $output_array[1]);
                if (!in_array((string)$reflectionType, [
                    'int', 'integer',
                    'string',
                    'float', 'double',
                    'bool', 'boolean',
                    'array',
                ], true)) {
                    return $reflectionType;
                }
            }
            return null;
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
                || $resolvedType->getValueType() instanceof Object_
                || $resolvedType->getValueType() instanceof Mixed_;

            if ($isValid) {
                return $resolvedType;
            }
        }

        if (in_array(get_class($resolvedType), [
            Boolean::class,
            Float_::class,
            Integer::class,
            String_::class,
            Object_::class,
        ], true)) {
            return $resolvedType;
        }

        throw CouldNotResolveDocblockType::create($type, $reflectionProperty);
    }
}
