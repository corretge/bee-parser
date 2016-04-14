<?php
/*
 * This file is part of the UbqOS project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ubqos\Bee\Parser\Tests;

use Ubqos\Bee\Parser\Bee;
use Ubqos\Bee\Parser;

class AttributesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var Parser\Dumper
     */
    protected $dumper;

    protected function setUp()
    {
        $this->parser = new Parser();
        $this->dumper = new Parser\Dumper();
    }

    protected function tearDown()
    {
        unset($this->parser);
        unset($this->dumper);
    }

    public function testAttributesMultiline()
    {
        $bee = <<< EOF
--- %BEE:1.0
foo: 
    (
        bar: A
        ber: B
    )
id: multiLine  
EOF;

        $expected = array (
            'foo' =>
                array (
                    '@attr' =>
                        array (
                            'bar' => 'A',
                            'ber' => 'B',
                        ),
                ),
            'id' => 'multiLine',
        );

        $res = $this->parser->parse($bee);

        $this->assertEquals(
            $expected,
            $res
        );
    }


    public function deprecatedTestAttributesOneLine()
    {
        $bee = <<< EOF
--- %BEE:1.0
foo: ( bar: A, ber: B )
id: multiLine  
EOF;

        $expected = array (
            'foo' =>
                array (
                    '@attr' =>
                        array (
                            'bar' => 'A',
                            'ber' => 'B',
                        ),
                ),
            'id' => 'multiLine',
        );

        $res = $this->parser->parse($bee);

        $this->assertEquals(
            $expected,
            $res
        );
    }

    /**
     *
     */
    public function testArgumentsWithParentesi()
    {
        $bee = <<< EOF
--- %BEE:1.0
division: identification
basedir: .
default: list
id: identification division
description: Test build
---
division: environment
id: environment division
composer: composer.json
---
division: data
id: data division
---
division: procedure
id: build
description: Lorem ipsum
target:
    if:
        available:
            file: "etc/local-config.ini"
        then:
            property:
                file: "etc/local-config.ini"
                override: false
    if:
        available:
            file: "etc/config.ini"
        then:
            property:
                file: "etc/config.ini"
                override: false
    property:
        file: "etc/config.ini"
        override: false

---
division: procedure
id: list
description: Lorem ipsum
target: ~
EOF;

        $this->parser->parseSections($bee);

    }


}

