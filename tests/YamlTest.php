<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
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

use Origin\Yaml\Yaml;
use Origin\Yaml\Exception\YamlException;

class YamlTest extends \PHPUnit\Framework\TestCase
{
    public function testFromArrayScalar()
    {
        $student = [
            'id' => 1234,
            'name' => 'james',
            'date' => '2019-05-05',
            'boolean' => false,
        ];
        $Yaml = Yaml::fromArray($student);
        $expected = <<< EOT
id: 1234
name: james
date: 2019-05-05
boolean: false
EOT;
        $this->assertStringContainsString($expected, $Yaml);
    }
    public function testFromArrayYaml()
    {
        $student = [
            'id' => 1234,
            'address' => [
                'line' => '458 Some Road
                Somewhere, Something', // multi line
                'city' => 'london',
            ],
            
        ];
        $Yaml = Yaml::fromArray($student);
       
        $expected = <<< EOT
id: 1234
address: 
  line: | 458 Some Road
                Somewhere, Something
  city: london
EOT;
        $this->assertStringContainsString($expected, $Yaml);
    }

    public function testFromList()
    {
        $students = ['tony','nick'];
        $Yaml = Yaml::fromArray($students);
        $expected = <<< EOT
- tony
- nick
EOT;
        $this->assertStringContainsString($expected, $Yaml);
    }
    public function testFromChildList()
    {
        $students = [
            ['name' => 'tony','phones' => ['1234-456']],
            ['name' => 'nick','phones' => ['1234-456','456-4334']],
        ];
        $Yaml = Yaml::fromArray($students);
        $expected = <<< EOT
- name: tony
  phones: 
    - 1234-456
- name: nick
  phones: 
    - 1234-456
    - 456-4334
EOT;

        $this->assertStringContainsString($expected, $Yaml);
    }

    public function testFromArrayMultiYamls()
    {
        $students = [
            'id' => 1234,
            'name' => 'tony',
            'addresess' => [
                ['street' => '1234 some road','city' => 'london'],
                ['street' => '546 some avenue','city' => 'london'],
            ],
        ];
        $Yaml = Yaml::fromArray($students);
        $expected = <<< EOT
id: 1234
name: tony
addresess: 
  - street: 1234 some road
    city: london
  - street: 546 some avenue
    city: london
EOT;
        $this->assertStringContainsString($expected, $Yaml);
    }

    public function testPlainScalarMultiline()
    {
        $Yaml = <<< EOF
multi:
  a
  b
  c
  d
name: test
EOF;
        $this->assertEquals('a b c d', Yaml::toArray($Yaml)['multi']);
    }

    public function testFromArrayMultiLevel()
    {
        $data = [
            'services' => [
                'app' => [
                    'build' => '.',
                    'depends_on' => [
                        'db',
                    ],
                ],
                'memcached' => [
                    'image' => 'memcached',
                ],
            ],
            'volumes' => [
                'mysql' => 'abc', // leaving this blank is a problem. works with docker. but cant parse it
            ],
        ];
        $Yaml = Yaml::fromArray($data);
        $expected = <<< EOT
services: 
  app: 
    build: .
    depends_on: 
      - db
  memcached: 
    image: memcached
volumes: 
  mysql: abc
EOT;
        $this->assertStringContainsString($expected, $Yaml);
    }

    public function testParseValues()
    {
        $Yaml = <<< EOF
enabled: true
disabled: false
empty: null
EOF;
        $result = Yaml::toArray($Yaml);
        $this->assertEquals(true, $result['enabled']);
        $this->assertEquals(false, $result['disabled']);
        $this->assertNull($result['empty']);
    }

    public function testParseIndexedList()
    {
        $Yaml = <<< EOT
---
# List of fruits
-
  name: james
-
  name: amy
EOT;
        $expected = [['name' => 'james'],['name' => 'amy']];
        $this->assertEquals($expected, Yaml::toArray($Yaml));
    }

    public function testParseList()
    {
        $Yaml = <<< EOT
---
# List of fruits
fruits:
    - Apple
    - Orange
    - Banana
EOT;
        $expected = ['fruits' => ['Apple','Orange','Banana']];
        $this->assertEquals($expected, Yaml::toArray($Yaml));
    }

    public function testParseDictonary()
    {
        $Yaml = <<< EOT
---
# Employee record
employee:
    name: James
    position: Senior Developer
EOT;
   
        $expected = ['employee' => ['name' => 'James','position' => 'Senior Developer']];
        $this->assertEquals($expected, Yaml::toArray($Yaml));
    }

    public function testParseRecordSet()
    {
        $Yaml = <<< EOT
---
# Employees 
- 100:
  name: James
  position: Senior Developer
- 200:
  name: Tony
  position: Manager

EOT;
        $expected = [
            '100' => ['name' => 'James','position' => 'Senior Developer'],
            '200' => ['name' => 'Tony','position' => 'Manager'],
        ];
        $this->assertEquals($expected, Yaml::toArray($Yaml));
    }

    public function testParseMultiLineBlock()
    {
        $Yaml = <<< EOT
block_1: |
  this is a multiline block
  of text
block_2: >
  this also is a multiline block
  of text
EOT;
        $expected = [
            'block_1' => "this is a multiline block\nof text", // literal
            'block_2' => 'this also is a multiline block of text', // folded
        ];
        $result = Yaml::toArray($Yaml);
  
        $this->assertSame($expected, Yaml::toArray($Yaml));
    }

    public function testComplicated()
    {
        $Yaml = <<< EOT
---
# Employee record
name: James Anderson
job: PHP developer
active: true
fruits:
    - Apple
    - Banana
phones:
    home: 0207 123 4567
    mobile: 123 456 567
addresses:
    - street: 2 Some road
      city: London
    - street: 5 Some avenue
      city: Manchester
description: |
  Lorem ipsum dolor sit amet, > 
  ea eum nihil sapientem, timeam
  constituto id per.
EOT;
       
        $expected = '{"name":"James Anderson","job":"PHP developer","active":true,"fruits":["Apple","Banana"],"phones":{"home":"0207 123 4567","mobile":"123 456 567"},"addresses":[{"street":"2 Some road","city":"London"},{"street":"5 Some avenue","city":"Manchester"}],"description":"Lorem ipsum dolor sit amet, > \nea eum nihil sapientem, timeam\nconstituto id per."}';
    
        $this->assertEquals($expected, json_encode(Yaml::toArray($Yaml)));
    }

    public function testParseChildNumericalList()
    {
        $Yaml = <<< EOT
# Employee record
name: James
addresses:
    -
      city: London
    -
      city: Liverpool
EOT;
        $expected = [['city' => 'London'],['city' => 'Liverpool']];
        $result = Yaml::toArray($Yaml);
                 
        $this->assertSame($expected, $result['addresses']);
    }

    public function testUsingTabsException()
    {
        $this->expectException(YamlException::class);
        $Yaml = "\tname: no tab please";
        Yaml::toArray($Yaml);
    }

    public function testMultiDocumentStreamException()
    {
        $this->expectException(YamlException::class);
        $Yaml = "...\nname: value...";
        Yaml::toArray($Yaml);
    }

    /**
     * This test was created to test parsing yaml values within yaml and the last
     * line which is an empty parent and was causing a permenet loop
     */
    public function testYamlWithinYaml()
    {
        $yaml = Yaml::toArray(file_get_contents(__DIR__ . '/Fixture/cloud-init.yml'));
        $this->assertEquals('b85eab4501694a0ee97e39b92db27b9c', md5(json_encode($yaml)));
    }
}
