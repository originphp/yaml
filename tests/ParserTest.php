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

namespace Origin\Test\Yaml;

use Origin\Yaml\YamlParser;

/**
 * Examples were taken or based from
 * @see https://docs.ansible.com/ansible/latest/reference_appendices/YAMLSyntax.html
 */
class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function testList()
    {
        $yaml = <<< EOT
# A list of tasty fruits
- Apple
- Orange
- Strawberry
- Mango
EOT;
   
        $expected = [
            0 => 'Apple',
            1 => 'Orange',
            2 => 'Strawberry',
            3 => 'Mango',
        ];

        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testDictonary()
    {
        $yaml = <<< EOT
# An employee record
name: Martin D'vloper
job: Developer
skill: Elite
EOT;
     
        $expected = [
            'name' => 'Martin D\'vloper',
            'job' => 'Developer',
            'skill' => 'Elite',
        ];
        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testMixed()
    {
        $yaml = <<< EOT
skills:
  - python
  - perl
  - pascal
EOT;
        
        $expected = [
            'skills' => [
                'python',
                'perl',
                'pascal'
            ]
        ];
       
        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testComplicated()
    {
        $yaml = <<< EOT
# An employee record
martin:
    name: Martin D'vloper
    job: Developer
    skill: Elite
EOT;
        
        $expected = [
            'martin' => [
                'name' => 'Martin D\'vloper',
                'job' => 'Developer',
                'skill' => 'Elite',
            ]
        ];
        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testListOfDictonary()
    {
        $yaml = <<< EOT
# Employee records
- martin:
    name: Martin D'vloper
    job: Developer
    skills:
        - python
        - perl
        - pascal
- tabitha:
    name: Tabitha Bitumen
    job: Developer
    skills:
        - lisp
        - fortran
        - erlang
EOT;
        $expected = [
            'martin' =>
            [
                'name' => 'Martin D\'vloper',
                'job' => 'Developer',
                'skills' =>
                [
                    0 => 'python',
                    1 => 'perl',
                    2 => 'pascal',
                ],
            ],
            'tabitha' =>
            [
                'name' => 'Tabitha Bitumen',
                'job' => 'Developer',
                'skills' =>
                [
                    0 => 'lisp',
                    1 => 'fortran',
                    2 => 'erlang',
                ],
            ],
        ];
       
        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testNestedLists()
    {
        $yaml = <<< EOT
name: default
steps:
  - name: backend
    image: golang
    commands:
        - go build
        - go test
EOT;
       
        $expected = [
            'name' => 'default',
            'steps' => [
                [
                    'name' => 'backend',
                    'image' => 'golang',
                    'commands' => [
                        'go build',
                        'go test',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testFull()
    {
        $yaml = <<< EOT
invoice: 34843
date: 2001-01-23
bill-to:
    given: Chris
    family: Dumars
    address:
        lines: |
            458 Walkman Dr.
            Suite #292
        city: Royal Oak
        state: MI
        postal: 48046
product:
    - sku: BL394D
      quantity: 4
      description: Basketball
      price: 450.00
    - sku: BL4438H
      quantity: 1
      description: Super Hoop
      price: 2392.00
tax: 251.42
total: 4443.52
comments:
    Late afternoon is best.
    Backup contact is Nancy
    Billsmer @ 338-4338.
EOT;

        $expected = [
            'invoice' => 34843,
            'date' => '2001-01-23',
            'bill-to' =>
            [
                'given' => 'Chris',
                'family' => 'Dumars',
                'address' => [
                    'lines' => "458 Walkman Dr.\nSuite #292",
                    'city' => 'Royal Oak',
                    'state' => 'MI',
                    'postal' => 48046,
                ],
            ],
            'product' => [
                [
                    'sku' => 'BL394D',
                    'quantity' => 4,
                    'description' => 'Basketball',
                    'price' => 450.0,
                ],
             
                [
                    'sku' => 'BL4438H',
                    'quantity' => 1,
                    'description' => 'Super Hoop',
                    'price' => 2392.0,
                ],
            ],
            'tax' => 251.42,
            'total' => 4443.52,
            'comments' => [
                0 => 'Late afternoon is best.',
                1 => 'Backup contact is Nancy',
                2 => 'Billsmer @ 338-4338.',
            ],
        ];
     
        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }

    public function testParseList()
    {
        $yaml = <<< EOT
---
# List of fruits
-
    name: james
-
    name: amy
EOT;
       
        $expected = [
            ['name' => 'james'],
            ['name' => 'amy']
        ];

        $this->assertEquals($expected, (new YamlParser($yaml))->toArray());
    }
}
