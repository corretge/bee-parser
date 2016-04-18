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

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new Parser();
    }

    protected function tearDown()
    {
        $this->parser = null;
    }

    /**
     * @dataProvider getDataFromSpecifications
     */
    public function testSpecifications($file, $expected, $bee, $comment)
    {
        $actual = var_export($this->parser->parse($bee), true);
        $this->assertEquals($expected, $actual, $comment);
    }

    public function getDataFromSpecifications()
    {
        $parser = new Parser();
        $path = __DIR__.'/Fixtures';

        $tests = array();
        $files = $parser->parse(file_get_contents($path.'/index.bee'));
        foreach ($files as $file) {
            $bees = file_get_contents($path.'/'.$file.'.bee');

            // split Bee Sections & Documents
            foreach (preg_split('/^---( %BEE\:1\.0)?/m', $bees) as $bee) {
                if (!$bee) {
                    continue;
                }

                $test = $parser->parse($bee);
                if (isset($test['todo']) && $test['todo']) {
                    // TODO
                } else {
                    eval('$expected = '.trim($test['php']).';');

                    $tests[] = array($file, var_export($expected, true), $test['bee'], $test['test']);
                }
            }
        }

        return $tests;
    }

    public function testTabsInYaml()
    {
        // test tabs in Bee
        $bees = array(
            "foo:\n	bar",
            "foo:\n 	bar",
            "foo:\n	 bar",
            "foo:\n 	 bar",
        );

        foreach ($bees as $bee) {
            try {
                $content = $this->parser->parse($bee);

                $this->fail('Bee sections must not contain tabs');
            } catch (\Exception $e) {
                $this->assertInstanceOf('\Exception', $e, 'Bee sections must not contain tabs');
                $this->assertEquals('A Bee section cannot contain tabs as indentation at line 2 (near "'.strpbrk($bee, "\t").'").', $e->getMessage(), ' Bee string must not contain tabs');
            }
        }
    }

    public function testEndOfTheDocumentMarker()
    {
        $bee = <<<'EOF'
--- %BEE:1.0
foo
...
EOF;

        $this->assertEquals('foo', $this->parser->parse($bee));
    }

    public function getBlockChompingTests()
    {
        $tests = array();

        $bee = <<<'EOF'
foo: |-
    one
    two
bar: |-
    one
    two

EOF;
        $expected = array(
            'foo' => "one\ntwo",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping strip with single trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |-
    one
    two

bar: |-
    one
    two


EOF;
        $expected = array(
            'foo' => "one\ntwo",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping strip with multiple trailing newlines'] = array($expected, $bee);

        $bee = <<<'EOF'
{}


EOF;
        $expected = array();
        $tests['Literal block chomping strip with multiple trailing newlines after a 1-liner'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |-
    one
    two
bar: |-
    one
    two
EOF;
        $expected = array(
            'foo' => "one\ntwo",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping strip without trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |
    one
    two
bar: |
    one
    two

EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo\n",
        );
        $tests['Literal block chomping clip with single trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |
    one
    two

bar: |
    one
    two


EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo\n",
        );
        $tests['Literal block chomping clip with multiple trailing newlines'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |
    one
    two
bar: |
    one
    two
EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping clip without trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |+
    one
    two
bar: |+
    one
    two

EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo\n",
        );
        $tests['Literal block chomping keep with single trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |+
    one
    two

bar: |+
    one
    two


EOF;
        $expected = array(
            'foo' => "one\ntwo\n\n",
            'bar' => "one\ntwo\n\n",
        );
        $tests['Literal block chomping keep with multiple trailing newlines'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: |+
    one
    two
bar: |+
    one
    two
EOF;
        $expected = array(
            'foo' => "one\ntwo\n",
            'bar' => "one\ntwo",
        );
        $tests['Literal block chomping keep without trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >-
    one
    two
bar: >-
    one
    two

EOF;
        $expected = array(
            'foo' => 'one two',
            'bar' => 'one two',
        );
        $tests['Folded block chomping strip with single trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >-
    one
    two

bar: >-
    one
    two


EOF;
        $expected = array(
            'foo' => 'one two',
            'bar' => 'one two',
        );
        $tests['Folded block chomping strip with multiple trailing newlines'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >-
    one
    two
bar: >-
    one
    two
EOF;
        $expected = array(
            'foo' => 'one two',
            'bar' => 'one two',
        );
        $tests['Folded block chomping strip without trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >
    one
    two
bar: >
    one
    two

EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => "one two\n",
        );
        $tests['Folded block chomping clip with single trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >
    one
    two

bar: >
    one
    two


EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => "one two\n",
        );
        $tests['Folded block chomping clip with multiple trailing newlines'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >
    one
    two
bar: >
    one
    two
EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => 'one two',
        );
        $tests['Folded block chomping clip without trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >+
    one
    two
bar: >+
    one
    two

EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => "one two\n",
        );
        $tests['Folded block chomping keep with single trailing newline'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >+
    one
    two

bar: >+
    one
    two


EOF;
        $expected = array(
            'foo' => "one two\n\n",
            'bar' => "one two\n\n",
        );
        $tests['Folded block chomping keep with multiple trailing newlines'] = array($expected, $bee);

        $bee = <<<'EOF'
foo: >+
    one
    two
bar: >+
    one
    two
EOF;
        $expected = array(
            'foo' => "one two\n",
            'bar' => 'one two',
        );
        $tests['Folded block chomping keep without trailing newline'] = array($expected, $bee);

        return $tests;
    }

    /**
     * @dataProvider getBlockChompingTests
     */
    public function testBlockChomping($expected, $bee)
    {
        $this->assertSame($expected, $this->parser->parse($bee));
    }

    /**
     * Regression test for issue #7989.
     *
     * @see https://github.com/symfony/symfony/issues/7989
     */
    public function testBlockLiteralWithLeadingNewlines()
    {
        $bee = <<<'EOF'
foo: |-


    bar

EOF;
        $expected = array(
            'foo' => "\n\nbar",
        );

        $this->assertSame($expected, $this->parser->parse($bee));
    }

    public function testObjectSupportEnabled()
    {
        $input = <<<EOF
foo: !php/object:O:24:"Ubqos\Bee\Parser\Tests\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $this->assertEquals(array('foo' => new B(), 'bar' => 1), $this->parser->parse($input, Bee::PARSE_OBJECT), '->parse() is able to parse objects');
    }

    /**
     * @dataProvider getObjectForMapTests
     */
    public function testObjectForMap($bee, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($bee, Bee::PARSE_OBJECT_FOR_MAP));
    }

    public function getObjectForMapTests()
    {
        $tests = array();

        $bee = <<<EOF
foo:
    fiz: [cat]
EOF;
        $expected = new \stdClass();
        $expected->foo = new \stdClass();
        $expected->foo->fiz = array('cat');
        $tests['mapping'] = array($bee, $expected);

        $bee = '{ "foo": "bar", "fiz": "cat" }';
        $expected = new \stdClass();
        $expected->foo = 'bar';
        $expected->fiz = 'cat';
        $tests['inline-mapping'] = array($bee, $expected);

        $bee = "foo: bar\nbaz: foobar";
        $expected = new \stdClass();
        $expected->foo = 'bar';
        $expected->baz = 'foobar';
        $tests['object-for-map-is-applied-after-parsing'] = array($bee, $expected);

        $bee = <<<EOT
array:
  - key: one
  - key: two
EOT;
        $expected = new \stdClass();
        $expected->array = array();
        $expected->array[0] = new \stdClass();
        $expected->array[0]->key = 'one';
        $expected->array[1] = new \stdClass();
        $expected->array[1]->key = 'two';
        $tests['nest-map-and-sequence'] = array($bee, $expected);

        $bee = <<<Bee
map:
  1: one
  2: two
Bee;
        $expected = new \stdClass();
        $expected->map = new \stdClass();
        $expected->map->{1} = 'one';
        $expected->map->{2} = 'two';
        $tests['numeric-keys'] = array($bee, $expected);

        $bee = <<<Bee
map:
  0: one
  1: two
Bee;
        $expected = new \stdClass();
        $expected->map = new \stdClass();
        $expected->map->{0} = 'one';
        $expected->map->{1} = 'two';
        $tests['zero-indexed-numeric-keys'] = array($bee, $expected);

        return $tests;
    }

    public function invalidDumpedObjectProvider()
    {
        $beeTag = <<<EOF
foo: !!php/object:O:30:"Symfony\Tests\Component\Yaml\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        $localTag = <<<EOF
foo: !php/object:O:30:"Symfony\Tests\Component\Yaml\B":1:{s:1:"b";s:3:"foo";}
bar: 1
EOF;
        return array(
            'yaml-tag' => array($beeTag),
            'local-tag' => array($localTag),
        );
    }

    /**
     * @dataProvider invalidDumpedObjectProvider
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     */
    public function testObjectsSupportDisabledWithExceptions($bee)
    {
        $this->parser->parse($bee, Bee::PARSE_EXCEPTION_ON_INVALID_TYPE);
    }

    /**
     * @requires extension iconv
     */
    public function testNonUtf8Exception()
    {
        $bees = array(
            iconv('UTF-8', 'ISO-8859-1', "foo: 'äöüß'"),
            iconv('UTF-8', 'ISO-8859-15', "euro: '€'"),
            iconv('UTF-8', 'CP1252', "cp1252: '©ÉÇáñ'"),
        );

        foreach ($bees as $bee) {
            try {
                $this->parser->parse($bee);

                $this->fail('charsets other than UTF-8 are rejected.');
            } catch (\Exception $e) {
                $this->assertInstanceOf('Ubqos\Bee\Parser\Exception\ParseException', $e, 'charsets other than UTF-8 are rejected.');
            }
        }
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     */
    public function testUnindentedCollectionException()
    {
        $bee = <<<'EOF'

collection:
-item1
-item2
-item3

EOF;

        $this->parser->parse($bee);
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     */
    public function testShortcutKeyUnindentedCollectionException()
    {
        $bee = <<<'EOF'

collection:
-  key: foo
  foo: bar

EOF;

        $this->parser->parse($bee);
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     * @expectedExceptionMessage Multiple documents are not supported.
     */
    public function testMultipleDocumentsNotSupportedException()
    {
        Bee::parse(<<<'EOL'
# Ranking of 1998 home runs
---
- Mark McGwire
- Sammy Sosa
- Ken Griffey

# Team ranking
---
- Chicago Cubs
- St Louis Cardinals
EOL
        );
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     */
    public function testSequenceInAMapping()
    {
        Bee::parse(<<<'EOF'
yaml:
  hash: me
  - array stuff
EOF
        );
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     */
    public function testMappingInASequence()
    {
        Bee::parse(<<<'EOF'
yaml:
  - array stuff
  hash: me
EOF
        );
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     * @expectedExceptionMessage missing colon
     */
    public function testScalarInSequence()
    {
        Bee::parse(<<<EOF
foo:
    - bar
"missing colon"
    foo: bar
EOF
        );
    }

    /**
     * > It is an error for two equal keys to appear in the same mapping node.
     * > In such a case the Bee processor may continue, ignoring the second
     * > `key: value` pair and issuing an appropriate warning. This strategy
     * > preserves a consistent information model for one-pass and random access
     * > applications.
     *
     * @see http://yaml.org/spec/1.2/spec.html#id2759572
     * @see http://yaml.org/spec/1.1/#id932806
     */
    public function testMappingDuplicateKeyBlock()
    {
        $input = <<<EOD
parent:
    child: first
    child: duplicate
parent:
    child: duplicate
    child: duplicate
EOD;
        $expected = array(
            'parent' => array(
                'child' => 'first',
            ),
        );
        $this->assertSame($expected, Bee::parse($input));
    }

    public function testMappingDuplicateKeyFlow()
    {
        $input = <<<EOD
parent: { child: first, child: duplicate }
parent: { child: duplicate, child: duplicate }
EOD;
        $expected = array(
            'parent' => array(
                'child' => 'first',
            ),
        );
        $this->assertSame($expected, Bee::parse($input));
    }

    public function testEmptyValue()
    {
        $input = <<<'EOF'
hash:
EOF;

        $this->assertEquals(array('hash' => null), Bee::parse($input));
    }

    public function testCommentAtTheRootIndent()
    {
        $this->assertEquals(array(
            'services' => array(
                'app.foo_service' => array(
                    'class' => 'Foo',
                ),
                'app/bar_service' => array(
                    'class' => 'Bar',
                ),
            ),
        ), Bee::parse(<<<'EOF'
# comment 1
services:
# comment 2
    # comment 3
    app.foo_service:
        class: Foo
# comment 4
    # comment 5
    app/bar_service:
        class: Bar
EOF
        ));
    }

    public function testStringBlockWithComments()
    {
        $this->assertEquals(array('content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        ), Bee::parse(<<<'EOF'
content: |
    # comment 1
    header

        # comment 2
        <body>
            <h1>title</h1>
        </body>

    footer # comment3
EOF
        ));
    }

    public function testFoldedStringBlockWithComments()
    {
        $this->assertEquals(array(array('content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        )), Bee::parse(<<<'EOF'
-
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF
        ));
    }

    public function testNestedFoldedStringBlockWithComments()
    {
        $this->assertEquals(array(array(
            'title' => 'some title',
            'content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
        )), Bee::parse(<<<'EOF'
-
    title: some title
    content: |
        # comment 1
        header

            # comment 2
            <body>
                <h1>title</h1>
            </body>

        footer # comment3
EOF
        ));
    }

    public function testReferenceResolvingInInlineStrings()
    {
        $this->assertEquals(array(
            'var' => 'var-value',
            'scalar' => 'var-value',
            'list' => array('var-value'),
            'list_in_list' => array(array('var-value')),
            'map_in_list' => array(array('key' => 'var-value')),
            'embedded_mapping' => array(array('key' => 'var-value')),
            'map' => array('key' => 'var-value'),
            'list_in_map' => array('key' => array('var-value')),
            'map_in_map' => array('foo' => array('bar' => 'var-value')),
        ), Bee::parse(<<<'EOF'
var:  &var var-value
scalar: *var
list: [ *var ]
list_in_list: [[ *var ]]
map_in_list: [ { key: *var } ]
embedded_mapping: [ key: *var ]
map: { key: *var }
list_in_map: { key: [*var] }
map_in_map: { foo: { bar: *var } }
EOF
        ));
    }

    public function testYamlDirective()
    {
        $bee = <<<'EOF'
%BEE 1.2
---
foo: 1
bar: 2
EOF;
        $this->assertEquals(array('foo' => 1, 'bar' => 2), $this->parser->parse($bee));
    }

    public function testFloatKeys()
    {
        $bee = <<<'EOF'
foo:
    1.2: "bar"
    1.3: "baz"
EOF;

        $expected = array(
            'foo' => array(
                '1.2' => 'bar',
                '1.3' => 'baz',
            ),
        );

        $this->assertEquals($expected, $this->parser->parse($bee));
    }

    /**
     * @expectedException \Ubqos\Bee\Parser\Exception\ParseException
     * @expectedExceptionMessage A colon cannot be used in an unquoted mapping value
     */
    public function testColonInMappingValueException()
    {
        $bee = <<<EOF
foo: bar: baz
EOF;

        $this->parser->parse($bee);
    }

    public function testColonInMappingValueExceptionNotTriggeredByColonInComment()
    {
        $bee = <<<EOT
foo:
    bar: foobar # Note: a comment after a colon
EOT;

        $this->assertSame(array('foo' => array('bar' => 'foobar')), $this->parser->parse($bee));
    }

    /**
     * @dataProvider getCommentLikeStringInScalarBlockData
     */
    public function testCommentLikeStringsAreNotStrippedInBlockScalars($bee, $expectedParserResult)
    {
        $this->assertSame($expectedParserResult, $this->parser->parse($bee));
    }

    public function getCommentLikeStringInScalarBlockData()
    {
        $tests = array();

        $bee = <<<'EOT'
pages:
    -
        title: some title
        content: |
            # comment 1
            header

                # comment 2
                <body>
                    <h1>title</h1>
                </body>

            footer # comment3
EOT;
        $expected = array(
            'pages' => array(
                array(
                    'title' => 'some title',
                    'content' => <<<'EOT'
# comment 1
header

    # comment 2
    <body>
        <h1>title</h1>
    </body>

footer # comment3
EOT
                    ,
                ),
            ),
        );
        $tests[] = array($bee, $expected);

        $bee = <<<'EOT'
test: |
    foo
    # bar
    baz
collection:
    - one: |
        foo
        # bar
        baz
    - two: |
        foo
        # bar
        baz
EOT;
        $expected = array(
            'test' => <<<'EOT'
foo
# bar
baz

EOT
            ,
            'collection' => array(
                array(
                    'one' => <<<'EOT'
foo
# bar
baz
EOT
                    ,
                ),
                array(
                    'two' => <<<'EOT'
foo
# bar
baz
EOT
                    ,
                ),
            ),
        );
        $tests[] = array($bee, $expected);

        $bee = <<<EOT
foo:
  bar:
    scalar-block: >
      line1
      line2>
  baz:
# comment
    foobar: ~
EOT;
        $expected = array(
            'foo' => array(
                'bar' => array(
                    'scalar-block' => 'line1 line2>',
                ),
                'baz' => array(
                    'foobar' => null,
                ),
            ),
        );
        $tests[] = array($bee, $expected);

        $bee = <<<'EOT'
a:
    b: hello
#    c: |
#        first row
#        second row
    d: hello
EOT;
        $expected = array(
            'a' => array(
                'b' => 'hello',
                'd' => 'hello',
            ),
        );
        $tests[] = array($bee, $expected);

        return $tests;
    }

    public function testBlankLinesAreParsedAsNewLinesInFoldedBlocks()
    {
        $bee = <<<EOT
test: >
    <h2>A heading</h2>

    <ul>
    <li>a list</li>
    <li>may be a good example</li>
    </ul>
EOT;

        $this->assertSame(
            array(
                'test' => <<<EOT
<h2>A heading</h2>
<ul> <li>a list</li> <li>may be a good example</li> </ul>
EOT
                ,
            ),
            $this->parser->parse($bee)
        );
    }

    public function testAdditionallyIndentedLinesAreParsedAsNewLinesInFoldedBlocks()
    {
        $bee = <<<EOT
test: >
    <h2>A heading</h2>

    <ul>
      <li>a list</li>
      <li>may be a good example</li>
    </ul>
EOT;

        $this->assertSame(
            array(
                'test' => <<<EOT
<h2>A heading</h2>
<ul>
  <li>a list</li>
  <li>may be a good example</li>
</ul>
EOT
                ,
            ),
            $this->parser->parse($bee)
        );
    }

    /**
     * @dataProvider getBinaryData
     */
    public function testParseBinaryData($data)
    {
        $this->assertSame(array('data' => 'Hello world'), $this->parser->parse($data));
    }

    public function getBinaryData()
    {
        return array(
            'enclosed with double quotes' => array('data: !!binary "SGVsbG8gd29ybGQ="'),
            'enclosed with single quotes' => array("data: !!binary 'SGVsbG8gd29ybGQ='"),
            'containing spaces' => array('data: !!binary  "SGVs bG8gd 29ybGQ="'),
            'in block scalar' => array(
                <<<EOT
data: !!binary |
    SGVsbG8gd29ybGQ=
EOT
    ),
            'containing spaces in block scalar' => array(
                <<<EOT
data: !!binary |
    SGVs bG8gd 29ybGQ=
EOT
    ),
        );
    }

    /**
     * @dataProvider getInvalidBinaryData
     */
    public function testParseInvalidBinaryData($data, $expectedMessage)
    {
        $this->setExpectedExceptionRegExp('\Ubqos\Bee\Parser\Exception\ParseException', $expectedMessage);

        $this->parser->parse($data);
    }

    public function getInvalidBinaryData()
    {
        return array(
            'length not a multiple of four' => array('data: !!binary "SGVsbG8d29ybGQ="', '/The normalized base64 encoded data \(data without whitespace characters\) length must be a multiple of four \(\d+ bytes given\)/'),
            'invalid characters' => array('!!binary "SGVsbG8#d29ybGQ="', '/The base64 encoded data \(.*\) contains invalid characters/'),
            'too many equals characters' => array('data: !!binary "SGVsbG8gd29yb==="', '/The base64 encoded data \(.*\) contains invalid characters/'),
            'misplaced equals character' => array('data: !!binary "SGVsbG8gd29ybG=Q"', '/The base64 encoded data \(.*\) contains invalid characters/'),
            'length not a multiple of four in block scalar' => array(
                <<<EOT
data: !!binary |
    SGVsbG8d29ybGQ=
EOT
                ,
                '/The normalized base64 encoded data \(data without whitespace characters\) length must be a multiple of four \(\d+ bytes given\)/',
            ),
            'invalid characters in block scalar' => array(
                <<<EOT
data: !!binary |
    SGVsbG8#d29ybGQ=
EOT
                ,
                '/The base64 encoded data \(.*\) contains invalid characters/',
            ),
            'too many equals characters in block scalar' => array(
                <<<EOT
data: !!binary |
    SGVsbG8gd29yb===
EOT
                ,
                '/The base64 encoded data \(.*\) contains invalid characters/',
            ),
            'misplaced equals character in block scalar' => array(
                <<<EOT
data: !!binary |
    SGVsbG8gd29ybG=Q
EOT
                ,
                '/The base64 encoded data \(.*\) contains invalid characters/',
            ),
        );
    }
}

class B
{
    public $b = 'foo';
}
