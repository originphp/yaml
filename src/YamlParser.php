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

/**
 * This has become quite big and is ready for a refactor.
 */
class YamlParser
{
    /**
     * The line endings
     */
    const EOF = "\r\n";

    /**
     * Copy of the source
     *
     * @var string
     */
    protected $src = null;

    /**
     * Array of the lines
     *
     * @var array
     */
    protected $lines = [];
    
    /**
     * Holds the line counter
     *
     * @var int
     */
    protected $i = 0;

    /**
     * Constructor
     *
     * @param string $src Yaml source
     */
    public function __construct(string $src = null)
    {
        if ($src) {
            $this->src = $src;
            $this->lines = $this->readLines($src);
        }
    }

    /**
     * This is used to manually create lines e.g list of lists.
     *
     * @param array $lines
     * @return array
     */
    public function lines(array $lines = null): array
    {
        if ($lines) {
            $this->lines = $lines;
        }

        return $this->lines;
    }

    /**
     * Help identify record sets
     *
     * @param int $from
     * @return array
     */
    protected function findRecordSets(int $from): array
    {
        $lines = count($this->lines);
        $results = [];
        $spaces = strpos($this->lines[$from], ltrim($this->lines[$from]));
        
        $start = null;
        
        for ($w = $from;$w < $lines;$w++) {
            $marker = ltrim($this->lines[$w]);
            if ($marker[0] === '-') {
                if ($start !== null) {
                    $results[$start] = $w - 1;
                }
                $start = $w;
                if ($marker !== '-') {
                    $marker = substr($marker, 1);
                }
            }
            if (strpos($this->lines[$w], $marker) < $spaces) {
                $results[$start] = $w - 1;
                $start = null;
                break; // its parent
            } elseif ($w === ($lines - 1) && $start) {
                $results[$start] = $w; // Reached end of of file
            }
        }

        return $results;
    }

    /**
     * Parses the array
     *
     * @param integer $lineNo from
     * @return array
     */
    protected function parse(int $lineNo = 0): array
    {
        $result = [];
        $lines = count($this->lines);
     
        $spaces = $lastSpaces = 0;
   
   
        for ($i = $lineNo;$i < $lines;$i++) {
            $line = $this->lines[$i];
            $marker = trim($line);
       
            // Skip comments,empty lines  and directive
            if ($marker === '' || $marker[0] === '#' || $line === '---' || substr($line, 0, 5) === '%YAML') {
                $this->i = $i;
                continue;
            }

            if ($line[0] === "\t") {
                throw new YamlException('YAML documents should not use tabs for indentation');
            }
            if ($line === '...') {
                throw new YamlException('Multiple document streams are not supported.');
            }
            
            // Identify node level
            $spaces = strpos($line, $marker);
            if ($spaces > $lastSpaces) {
                $lastSpaces = $spaces;
            } elseif ($spaces < $lastSpaces) {
                break;
            }

             
            // Walk Forward to handle multiline data folded and literal
            if ($this->isScalar($line) && (substr($line, -3) === ': |' || substr($line, -3) === ': >')) {
                list($key, $value) = explode(': ', ltrim($line));
                
                $indent = strlen($line) - strlen(ltrim($line));
               
                $trimFrom = $indent + 2;

                $value = '';
                /**
                 * > Folded style: line breaks replaced with space
                 * | literal style: line breaks count
                 * @see https://Yaml.org/spec/current.html#id2539942
                 */
                $break = substr($line, -1) === '>' ? ' ' : "\n";
             
                for ($w = $i + 1;$w < $lines;$w++) {
                    $nextLine = substr($this->lines[$w], $trimFrom); #
                    $nextLine = rtrim($nextLine); // clean up end of lines
                      
                    // Handle multilines which are on the last lastline
                    if ($w === $lines - 1) {
                        $value .= $nextLine . $break;
                    }

                    if (substr($this->lines[$w], 0, $indent +2) !== str_repeat(' ', $indent+2) || $w === $lines - 1) {
                        $result[$key] = rtrim($value);
                        break;
                    }

                    $value .= $nextLine . $break;
                }

                $this->i = $i = $w - 1;
                continue;
            }

            // Walk forward for multi line data
            if (! $this->isList($line) && ! $this->isScalar($line) && ! $this->isParent($line)) {
                $parentLine = $this->lines[$i - 1];
                if (! $this->isParent($parentLine)) {
                    continue; // Skip if there is no parent
                }
                $block = trim($line);
                for ($w = $i + 1;$w < $lines;$w++) {
                    $nextLine = trim($this->lines[$w]);
                    if (! $this->isList($nextLine) && ! $this->isScalar($nextLine) && ! $this->isParent($nextLine)) {
                        $block .= ' ' . $nextLine; // In the plain scalar,newlines become spaces
                    } else {
                        break;
                    }
                }
                $this->i = $i = $w - 1;
             
                $result['__plain_scalar__'] = $block;
                continue;
            }
          

            // Handle Lists
            if ($this->isList($line)) {
                $trimmedLine = ltrim(' '. substr(ltrim($line), 2)); // work with any number of spaces;
               
                if (trim($line) !== '-' && ! $this->isParent($trimmedLine) && ! $this->isScalar($trimmedLine)) {
                    $result[] = $this->readValue($trimmedLine);
                } elseif ($this->isParent($trimmedLine)) {
                    $key = substr(ltrim($trimmedLine), 0, -1);
                    $result[$key] = $this->parse($i + 1);
                    $i = $this->i;
                } else {
                    /**
                     * Deal with list sets. Going to seperate from the rest. remove
                     * the - from the start each set and pass through the parser (is this a hack?)
                     */
                    $sets = $this->findRecordSets($i);

                    foreach ($sets as $start => $finish) {
                        $setLines = [];
                        for ($ii = $start;$ii < $finish + 1;$ii++) {
                            $setLine = $this->lines[$ii];
                       
                            if ($ii === $start) {
                                if (trim($setLine) === '-') {
                                    continue;
                                } else {
                                    $setLine = str_replace('- ', '  ', $setLine); // Associate
                                }
                            }
                            $setLines[] = $setLine;
                        }
                 
                        $me = new YamlParser();
                        $me->lines($setLines);
                        $result[] = $me->toArray();
                    }
                    $i = $finish;
                }
            } elseif ($this->isScalar($line)) {
                list($key, $value) = explode(': ', ltrim($line));
                $result[rtrim($key)] = $this->readValue($value);
            } elseif ($this->isParent($line)) {
                $line = trim($line);
                $key = substr($line, 0, -1);
            
                $key = rtrim($key);   // remove ending spaces e.g. invoice   :
               
                // terminate if there are no more lines
                if ($i + 1 === $lines) {
                    $result[$key] = null;
                    break;
                }

                // Check if next line is part of same node (e.g. empty array)
                $nextLine = $this->lines[$i + 1];
                if ($this->isScalar($nextLine) || $this->isParent($nextLine)) {
                    $nextLineSpaces = strpos($nextLine, trim($nextLine));
                    if ($nextLineSpaces <= $spaces) {
                        $result[$key] = [];
                        continue;
                    }
                }

                $result[$key] = $this->parse($i + 1);
                // Walk backward
                if (isset($result[$key]['__plain_scalar__'])) {
                    $result[$key] = $result[$key]['__plain_scalar__'];
                }
                
                $i = $this->i;
            }
            $this->i = $i;
        }
     
        return $result;
    }

    /**
     * Converts a string into an array of lines
     *
     * @param string $string
     * @return array
     */
    protected function readLines(string $string): array
    {
        $lines = [];
        $lines[] = $line = strtok($string, static::EOF);
        while ($line !== false) {
            $line = strtok(static::EOF);
            if ($line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Checks if a line is parent
     *
     * @param string $line
     * @return boolean
     */
    protected function isParent(string $line): bool
    {
        return (substr(trim($line), -1) === ':');
    }

    /**
     * Checks if a line is scalar value
     * @internal the space is important
     *
     * @param string $line
     * @return boolean
     */
    protected function isScalar(string $line): bool
    {
        return (strpos($line, ': ') !== false);
    }

    /**
     * Checks if line is a list
     *
     * @param string $line
     * @return bool
     */
    protected function isList(string $line): bool
    {
        $line = trim($line);

        return (substr($line, 0, 2) === '- ') || $line === '-';
    }

    /**
     * Converts the string into an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->parse();
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
    protected function readValue($value)
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
