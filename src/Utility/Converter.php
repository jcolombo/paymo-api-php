<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 3:44 PM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Jcolombo\PaymoApiPhp\Utility;

use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;

/**
 * Class Converter
 * Utility class for storing conversion methods that transform basic data
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class Converter
{

    /**
     * Get the PHP primitive data type from the prop definition types (for use in comparing values to expected ones)
     *
     * @param string $type The packages defined set of property types which are loosely based on real primitives
     *                     combined with the original API names for property types
     *
     * @return string The PHP equivalent datatype for type checking (except timestamp which is a special type used in
     *                this package)
     */
    public static function getPrimitiveType($type)
    {
        if (strpos($type, 'resource:') !== false) {
            $type = 'integer';
        } elseif (strpos($type, 'collection:') !== false) {
            $type = 'integer';
        }
        switch ($type) {
            case('datetime'):
                $cast = 'timestamp';
                break;
            case('array'):
            case('integer'):
                $cast = 'integer';
                break;
            case('double'):
            case('decimal'):
                $cast = 'double';
                break;
            case('boolean'):
                $cast = 'boolean';
                break;
            case('email'):
            case('url'):
            case('text'):
            default:
                $cast = 'string';
                break;
        }

        return $cast;
    }

    /**
     * Format the RequestCondition object to a valid string representation for API where call inclusion
     *
     * @param RequestCondition $w The condition to format into a valid string format for API where calls
     *
     * @return string|null The valid where condition or NULL if it does not meet any valid operators
     */
    public static function convertOperatorValue(RequestCondition $w)
    {
        $value = self::convertValueForFilter($w->dataType, $w->value);
        $ops = AbstractEntity::VALID_OPERATORS;
        $operator = $w->operator;
        if (in_array($operator, $ops)) {
            switch ($operator) {
                case('range'):
                    $capOp = $value[2] ? '<=' : '<';

                    return "{$w->prop}>={$value[0]} and {$w->prop}{$capOp}{$value[1]}";
                    break;
                case('in'):
                case('not in'):
                    return "{$w->prop} {$operator} ({$value})";
                    break;
                case('like'):
                case('not like'):
                    return "{$w->prop} {$operator} \"{$value}\"";
                    break;
                case('='):
                case('<='):
                case('<'):
                case('>='):
                case('>'):
                case('!='):
                default:
                    return "{$w->prop}{$operator}{$value}";
                    break;
            }
        }

        return null;
    }

    /**
     * Force the value of the where condition to be typecast properly to match the type of property being searched on
     *
     * @param string $type  The type of the prop as defined in the resource constant of all valid property types
     * @param mixed  $value The value sent from the RequestCondition to be used in the API where call
     *
     * @return array|false|int|string Returns the typecasted value that gets plugged into the operation value position
     *                                when calling the convertOperatorValue call
     */
    public static function convertValueForFilter($type, $value)
    {
        if (strpos($type, 'resource:') !== false) {
            $type = 'integer';
        } elseif (strpos($type, 'collection:') !== false) {
            $type = 'integer';
        }
        switch ($type) {
            case('datetime'):
                $cast = is_array($value) ? 'timestamp[]' : 'timestamp';
                break;
            case('array'):
            case('integer'):
                $cast = is_array($value) ? 'integer[]' : 'integer';
                break;
            case('double'):
            case('decimal'):
                $cast = is_array($value) ? 'double[]' : 'double';
                break;
            case('boolean'):
                $cast = 'boolean';
                break;
            case('email'):
            case('url'):
            case('text'):
            default:
                $cast = is_array($value) ? 'string[]' : 'string';
                break;
        }

        switch ($cast) {
            case('timestamp'):
                $value = is_int($value) ? $value : strtotime((string) $value);
                break;
            case('timestamp[]'):
                $value = [
                    is_int($value[0]) ? $value : strtotime((string) $value[0]),
                    is_int($value[1]) ? $value : strtotime((string) $value[1]),
                    isset($value[2]) && is_bool($value[2]) ? $value[2] : false
                ];
                break;
            case('double[]'):
                array_walk($value, function (&$i) { $i = (double) $i; });
                $value = implode(',', $value);
                break;
            case('integer[]'):
                array_walk($value, function (&$i) { $i = (int) $i; });
                $value = implode(',', $value);
                break;
            case('string[]'):
                array_walk($value, function (&$i) { $i = (string) $i; });
                $value = '"'.implode('","', $value).'"';
                break;
            case('boolean'):
                $value = $value !== false && $value !== 'false' ? 'true' : 'false';
                break;
            case('double'):
                $value = (double) $value;
                break;
            case('integer'):
                $value = (int) $value;
                break;
            case('string'):
            default:
                $value = (string) $value;
                break;
        }

        return $value;
    }

}