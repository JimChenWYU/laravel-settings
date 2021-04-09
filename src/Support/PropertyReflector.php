<?php

namespace Spatie\LaravelSettings\Support;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Null_;
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
        if (method_exists($reflectionProperty, 'getType')) {
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
                if (! in_array((string)$reflectionType, [
                    'int', 'integer',
                    'string',
                    'float', 'double',
                    'bool', 'boolean',
                    'array<string|int,mixed>',
                ], true)) {
                    return $reflectionType;
                }
            }

            return null;
        }

        if (! $reflectionType instanceof ReflectionNamedType) {
            return null;
        }

        $builtInTypes = [
            'int',
            'string',
            'float',
            'bool',
            'mixed',
            'array',
        ];

        if (in_array($reflectionType->getName(), $builtInTypes)) {
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

        $isValidPrimitive = $resolvedType instanceof Boolean
            || $resolvedType instanceof Float_
            || $resolvedType instanceof Integer
            || $resolvedType instanceof String_
            || $resolvedType instanceof Object_
            || $resolvedType instanceof Mixed_
        ;

        if ($isValidPrimitive) {
            return $resolvedType;
        }

        if ($resolvedType instanceof Compound) {
            return self::reflectCompound($reflectionProperty, $resolvedType);
        }

        if ($resolvedType instanceof Nullable) {
            return new Nullable(self::reflectDocblock($reflectionProperty, (string) $resolvedType->getActualType()));
        }

        if ($resolvedType instanceof AbstractList) {
            $listType = get_class($resolvedType);

            return new $listType(
                self::reflectDocblock($reflectionProperty, (string) $resolvedType->getValueType()),
                $resolvedType->getKeyType()
            );
        }

        throw CouldNotResolveDocblockType::create($type, $reflectionProperty);
    }

    private static function reflectCompound(
        ReflectionProperty $reflectionProperty,
        Compound $compound
    ): Nullable {
        if ($compound->getIterator()->count() !== 2 || ! $compound->contains(new Null_())) {
            throw CouldNotResolveDocblockType::create((string) $compound, $reflectionProperty);
        }

        $other = current(array_filter(
            iterator_to_array($compound->getIterator()),
            function (Type $type) {
                return ! $type instanceof Null_;
            }
        ));

        return new Nullable(self::reflectDocblock($reflectionProperty, (string) $other));
    }
}
