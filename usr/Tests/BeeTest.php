<?php
/*
 * This file is part of the UbqOS project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ubqos\Bee\Parser\Tests;

use Ubqos\Bee\Parser\Bee;

class BeeTest extends \PHPUnit_Framework_TestCase
{
    public function testParseAndDump()
    {
        $data = array('lorem' => 'ipsum', 'dolor' => 'sit');
        $bee = Bee::dump($data);
        $parsed = Bee::parse($bee);
        $this->assertEquals($data, $parsed);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The indentation must be greater than zero
     */
    public function testZeroIndentationThrowsException()
    {
        Bee::dump(array('lorem' => 'ipsum', 'dolor' => 'sit'), 2, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The indentation must be greater than zero
     */
    public function testNegativeIndentationThrowsException()
    {
        Bee::dump(array('lorem' => 'ipsum', 'dolor' => 'sit'), 2, -4);
    }
}
