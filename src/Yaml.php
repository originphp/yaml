<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
* This utility is for reading and writing YAML files, note. it is does not cover the complete specification.
* @see https://Yaml.org/refcard.html
*
* The goal of this utility is to provide basic YAML functionality to read and write configuration files and data from the
* database which can be read or edited in a user friendly way.
*
* It supports:
* - scalar values
* - comments
* - lists (block sequences @see https://Yaml.org/spec/current.html#id2543032)
* - dictonaries
* - dictonary lists
* - scalar blocks (plain, literal and folded)
*
* It currently does not support:
* - multiline quoted scalars example
* quoted: "So does this
* quoted scalar."
* However use description a string with \n or \r\n works fine (use literal | or folded >)
* - multiple documents in a stream @see https://Yaml.org/spec/current.html#id2502724
* - Surround in-line series branch. e.g [ ]
* - Surround in-line keyed branch. { }
*
* Known issues: parsing a docker compose file, the volumes for mysql-data is the value.
* volumes:
* mysql-data:
* @see https://Yaml.org/refcard.html
 */
declare(strict_types = 1);
namespace Origin\Yaml;

use Origin\Yaml\Exception\YamlException;

class Yaml
{
    const EOF = "\r\n";

    protected static $indent = 2;
    protected static $lines = [];

    /**
     * Converts a YAML string into an Array
     *
     * @param string $string
     * @return array
     */
    public static function toArray(string $string) : array
    {
        $parser = new YamlParser($string);
        return $parser->toArray();
    }
  
    /**
     * Converts an array into a YAML string
     *
     * @param array $array
     * @return string
     */
    public static function fromArray(array $array) : string
    {
        return self::dump($array);
    }

    protected static function dump(array $array, int $indent = 0, $isList = false)
    {
        $output = '';
        $line = 0;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $output .= self::dump($value, $indent, true);
                } else {
                    $output .= str_repeat(' ', $indent)  . "{$key}: \n";
                    $output .= self::dump($value, $indent + self::$indent);
                }
            } else {
                $value = self::dumpValue($value);
                if (is_int($key)) {
                    $string = "- {$value}";
                } else {
                    $string = "{$key}: {$value}";
                }
                if ($isList && $line == 0) {
                    $string = '- ' . $string;
                }
                $output .= str_repeat(' ', $indent) . "{$string}\n";
                if ($isList && $line == 0) {
                    $indent = $indent + 2;
                }
            }
            $line ++;
        }

        return $output;
    }
    
    protected static function dumpValue($value)
    {
        if (is_bool($value)) {
            return $value?'true':'false';
        }
        if (is_null($value)) {
            return null;
        }
        if (is_string($value) && strpos($value, "\n") !== false) {
            $value = "| {$value}";
        }

        return $value;
    }
}
