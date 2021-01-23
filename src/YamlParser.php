<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types = 1);
namespace Origin\Yaml;

use Origin\Yaml\Exception\YamlException;

class YamlParser
{
    private $yaml = null;
    private $lines = [];

    public function __construct(string $yaml)
    {
        $this->yaml = $yaml;
        $this->lines = preg_split("/\r\n|\n|\r/", $yaml);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $max = count($this->lines);
        $out = [];
        
        $syntax = new YamlSyntax();

        for ($i = 0;$i < $max;$i++) {
            $line = $this->lines[$i];
            $marker = trim($line);

            // Skip comments, empty lines and directive
            if ($marker === '' || $marker[0] === '#' || substr($line, 0, 3) === '---' || substr($line, 0, 5) === '%YAML') {
                continue;
            }
         
            if ($line[0] === "\t") {
                throw new YamlException('YAML documents should not use tabs for indentation');
            }

            if ($line === '...') {
                throw new YamlException('Multiple document streams are not supported.');
            }

            $level = $this->getLevel($line);
            $spansMultipleLines = $syntax->isDictionary($line) && ($syntax->isFoldedBlockScalar($line) || $syntax->isLiteralBlockScalar($line));
           
            // @example name: value
            if ($syntax->isDictionary($line) && ! $syntax->isList($line) && ! $syntax->isArray($line) && ! $spansMultipleLines) {
                list($key, $value) = explode(':', ltrim($line));
                $out[trim($key)] = $this->castValue(trim($value));
                continue;
            }
           
            // @example - foo
            if ($syntax->isList($line) && ! $syntax->isDictionary($line) && ! $syntax->isArray($line)) {
                $out[] = substr(ltrim($line), 2);
                continue;
            }
            
            // work with empty lines
            if (array_key_exists($i + 1, $this->lines) && empty($this->lines[$i + 1])) {

                // an empty array with no look foward is trouble
                if ($syntax->isArray($line)) {
                    list($key) = explode(':', ltrim($line));
                    $out[trim($key)] = null;
                }
                continue;
            }

            // Lookforward functions beyond here
            if (! isset($this->lines[$i + 1])) {
                $out[] = $this->unlevel($line, $level); // comment line maybe?
                continue;
            }

            $nextLevel = $this->getLevel($this->lines[$i + 1]);
          
            $buffer = [];

            /**
             * address: | or address: ^
             * address:
             *   lines: |
             *     458 Walkman Dr.
             *     Suite #292
             */
            if ($spansMultipleLines) {
                $fold = preg_match('/: >/', trim($line));
       
                $i = $this->walkFoward($i, $max, $buffer, $nextLevel);

                list($key, ) = explode(':', ltrim($line));
                $out[$key] = $this->flatten($buffer, $nextLevel, $fold ? ' ' : PHP_EOL);
                continue;
            }
            
            /**
             * Parent
             * martin:
             *    name: Martin D'vloper
             */
            if ($syntax->isArray($line) && ! $syntax->isList($line)) {
                $buffer[] = $line;
              
                $i = $this->walkFoward($i, $max, $buffer, $nextLevel);

                $firstLine = array_shift($buffer);
                list($key, ) = explode(':', ltrim($firstLine));
             
                $out[rtrim($key)] = $this->parseArray($buffer, $nextLevel);
               
                continue;
            }

            /**
             * List of dictonary:
             * - martin:
             *    name: Martin D'vloper
             */
            if ($syntax->isArray($line) && $syntax->isList($line)) {
                $buffer[] = substr($line, 2);
               
                $i = $this->walkFoward($i, $max, $buffer, $nextLevel);

                $firstLine = array_shift($buffer);
                list($key, ) = explode(':', ltrim($firstLine));
             
                $out[rtrim($key)] = $this->parseArray($buffer, $nextLevel);
        
                continue;
            }

            /**
             * product:
             *   - sku: BL394D
             *     quantity: 4
             */
            if ($syntax->isList($line) && $syntax->isDictionary($line)) {
                $buffer[] = substr(ltrim($line), 2); // remove leading -
                
                $i = $this->walkFoward($i, $max, $buffer, $nextLevel);
              
                $out[] = $this->parseArray($buffer, $nextLevel);
                continue;
            }

            /**
             * Sequence mapping
             * @see https://www.tutorialspoint.com/yaml/yaml_collections_and_structures.htm
             * -
             *   name: james
             * -
             *   name: amy
             */
            if (trim($line) === '-') {
                $i = $this->walkFoward($i, $max, $buffer, $nextLevel);
         
                $out[] = $this->parseArray($buffer, $nextLevel);
                continue;
            }

            $out[] = $this->unlevel($line, $level);
        }

        return $out;
    }

    /**
     * @param integer $current
     * @param integer $max
     * @param array $buffer
     * @param integer $currentLevel
     * @return int
     */
    protected function walkFoward(int $current, int $max, array &$buffer, int $currentLevel): int
    {
        for ($ii = $current + 1; $ii < $max; $ii++) {
            if (empty(trim($this->lines[$ii]))) {
                continue;
            }
            if ($this->getLevel($this->lines[$ii]) < $currentLevel) {
                break;
            }
            $buffer[] = $this->lines[$ii];
            $current = $ii;
        }

        return $current;
    }

    /**
     * @param array $buffer
     * @param integer $nextLevel
     * @return array
     */
    protected function parseArray(array $buffer, int $nextLevel): array
    {
        $data = $this->flatten($buffer, $nextLevel);

        return (new YamlParser($data))->toArray();
    }
   
    /**
     * @param array $data
     * @param integer $level
     * @return string
     */
    protected function flatten(array $data, int $level = 0, string $glue = PHP_EOL): string
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->unlevel($value, $level);
        }

        return implode($glue, $data);
    }

    /**
     * @param string $data
     * @param integer $level
     * @return string
     */
    protected function unlevel(string $data, int $level): string
    {
        $needle = str_repeat(' ', $level);
        $length = mb_strlen($needle);
        if (substr($data, 0, $length) === $needle) {
            $data = substr($data, $length);
        }

        return $data;
    }

    /**
     * @param string $line
     * @return integer
     */
    protected function getLevel(string $line): int
    {
        return strpos($line, trim($line));
    }

    /**
     * Converts incoming values such as bools, nulls, empty arrays or ""
     *
     * @see https://Yaml.org/type/bool.html
     * @see https://yaml.org/spec/1.2/spec.html
     *
     * @param mixed $value
     * @return mixed
     */
    protected function castValue($value)
    {
        if (in_array($value, ['true','True','TRUE'])) {
            return true;
        }
        if (in_array($value, ['false','False','FALSE'])) {
            return false;
        }
        // empty ie '' should map to null
        if (in_array($value, ['null','Null','NULL','~',''])) {
            return null;
        }

        if (is_numeric($value)) {
            if (preg_match('/^[-+]?([0-9]+)$/', $value)) {
                return (int) $value;
            }

            return (float) $value;
        }
       
        // json is offical subset of YAML, so these values should work
        if ($value === '[]') {
            return [];
        }
        if ($value === '""') {
            return '';
        }
        
        return trim($value, '"\''); // remove quotes spaces etc
    }
}
