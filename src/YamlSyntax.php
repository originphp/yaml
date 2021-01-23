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

class YamlSyntax
{
    /**
     * Checks if its a list
     *
     * @param string $line
     * @return boolean
     */
    public function isList(string $line): bool
    {
        return substr(trim($line), 0, 2) === '- ';
    }

    /**
     * Checks if its a Dictionary
     *
     * @param string $line
     * @return boolean
     */
    public function isDictionary(string $line): bool
    {
        return (bool) preg_match('/: /', $line);
    }

    /**
     * Checks if it is an array (line must end with no null value)
     *
     * @param string $line
     * @return boolean
     */
    public function isArray(string $line): bool
    {
        return substr($line, -1) === ':';
    }

    /**

     * @param string $line
     * @return boolean
     */
    public function isLiteralBlockScalar(string $line): bool
    {
        return substr(trim($line), -3) === ': |';
    }

    /**
     * @param string $line
     * @return boolean
     */
    public function isFoldedBlockScalar(string $line): bool
    {
        return substr(trim($line), -3) === ': >';
    }
}
