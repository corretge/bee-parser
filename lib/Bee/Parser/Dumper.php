<?php
/*
 * This file is part of the UbqOS project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ubqos\Bee\Parser;

/**
 * Dumper dumps PHP variables to YAML strings.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Dumper
{
    /**
     * The amount of spaces to use for indentation of nested nodes.
     *
     * @var int
     */
    protected $indentation;

    /**
     * @param int $indentation
     */
    public function __construct($indentation = 4)
    {
        if ($indentation < 1) {
            throw new \InvalidArgumentException('The indentation must be greater than zero.');
        }

        $this->indentation = $indentation;
    }

    /**
     * Dumps a PHP value to YAML.
     *
     * @param mixed $input  The PHP value
     * @param int   $inline The level where you switch to inline YAML
     * @param int   $indent The level of indentation (used internally)
     * @param int   $flags  A bit field of Bee::DUMP_* constants to customize the dumped YAML string
     *
     * @return string The YAML representation of the PHP value
     */
    public function dump($input, $inline = 0, $indent = 0, $flags = 0)
    {
        $output = '';
        $prefix = $indent ? str_repeat(' ', $indent) : '';

        if ($inline <= 0 || !is_array($input) || empty($input)) {
            $output .= $prefix.Inline::dump($input, $flags);
        } else {
            $isAHash = array_keys($input) !== range(0, count($input) - 1);

            foreach ($input as $key => $value) {
                if ($inline > 1 && Bee::DUMP_MULTI_LINE_LITERAL_BLOCK & $flags && is_string($value) && false !== strpos($value, "\n")) {
                    $output .= sprintf("%s%s%s |\n", $prefix, $isAHash ? Inline::dump($key, $flags).':' : '-', '');

                    foreach (preg_split('/\n|\r\n/', $value) as $row) {
                        $output .= sprintf("%s%s%s\n", $prefix, str_repeat(' ', $this->indentation), $row);
                    }

                    continue;
                }

                $willBeInlined = $inline - 1 <= 0 || !is_array($value) || empty($value);

                $output .= sprintf('%s%s%s%s',
                    $prefix,
                    $isAHash ? Inline::dump($key, $flags).':' : '-',
                    $willBeInlined ? ' ' : "\n",
                    $this->dump($value, $inline - 1, $willBeInlined ? 0 : $indent + $this->indentation, $flags)
                ).($willBeInlined ? "\n" : '');
            }
        }

        return $output;
    }
}
