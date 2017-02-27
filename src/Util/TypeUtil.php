<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Util;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;

final class TypeUtil
{
    public static function getInternalType($type) : string
    {
        if (\is_string($type)) {
            switch ($type) {
                case 'void':
                    return 'IS_VOID';
                case 'int':
                    return 'IS_LONG';
                case 'float':
                    return 'IS_DOUBLE';
                case 'array':
                    return 'IS_ARRAY';
                case 'callable':
                    return 'IS_CALLABLE';
                case 'bool':
                    return '_IS_BOOL';
                case 'string':
                    return 'IS_STRING';
            }
        }
        if ($type instanceof FullyQualified) {
            return 'IS_OBJECT';
        }
        if ($type instanceof NullableType) {
            return 'IS_UNKNOWN!';
        }
    }
}
