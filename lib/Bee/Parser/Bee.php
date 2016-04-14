<?php
/*
 * This file is part of the UbqOS project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ubqos\Bee\Parser;

use Ubqos\Bee\Parser\Exception\ParseException;
use Ubqos\Bee\Parser;

/**
 * Bee class offers convenience methods to load and dump Bee strings.
 *
 * Original YAML class by:
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * New one Bee class by:
 * @author Àlex Corretgé <alex@corretge.cat>
 */
class Bee
{
    const DUMP_OBJECT = 1;
    const PARSE_EXCEPTION_ON_INVALID_TYPE = 2;
    const PARSE_OBJECT = 4;
    const PARSE_OBJECT_FOR_MAP = 8;
    const DUMP_EXCEPTION_ON_INVALID_TYPE = 16;
    const PARSE_DATETIME = 32;
    const DUMP_OBJECT_AS_MAP = 64;
    const DUMP_MULTI_LINE_LITERAL_BLOCK = 128;

    /**
     * Parses Bee into a PHP value.
     *
     *  Usage:
     *  <code>
     *   $array = Bee::parse(file_get_contents('config.bee'));
     *   print_r($array);
     *  </code>
     *
     * @param string $input A string containing Bee
     * @param int    $flags A bit field of PARSE_* constants to customize the Bee parser behavior
     *
     * @return mixed The Bee converted to a PHP value
     *
     * @throws ParseException If the Bee is not valid
     */
    public static function parse($input, $flags = 0)
    {
        $bee = new Parser();

        return $bee->parse($input, $flags);
    }

    /**
     * Dumps a PHP array to a Bee string.
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into friendly Bee.
     *
     * @param array $array  PHP array
     * @param int   $inline The level where you switch to inline Bee
     * @param int   $indent The amount of spaces to use for indentation of nested nodes.
     * @param int   $flags  A bit field of DUMP_* constants to customize the dumped Bee string
     *
     * @return string A Bee string representing the original PHP array
     */
    public static function dump($array, $inline = 2, $indent = 4, $flags = 0)
    {
        $bee = new Dumper($indent);

        /**
         * indent is passed in construction phase. dump has an
         * internal dump, set to zero.
         */
        return $bee->dump($array, $inline, 0, $flags);
    }
}
