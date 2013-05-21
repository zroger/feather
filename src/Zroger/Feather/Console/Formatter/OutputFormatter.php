<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather\Console\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatter as BaseOutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatter extends BaseOutputFormatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct($decorated = null, array $styles = array())
    {
        parent::__construct(
            $decorated,
            array_merge(
                array(
                    'emergency' => new OutputFormatterStyle('red'),
                    'alert'     => new OutputFormatterStyle('red'),
                    'critical'  => new OutputFormatterStyle('red'),
                    'error'     => new OutputFormatterStyle('red'),
                    'warning'   => new OutputFormatterStyle('yellow'),
                    'notice'    => new OutputFormatterStyle('cyan'),
                    'info'      => new OutputFormatterStyle('cyan'),
                    'debug'     => new OutputFormatterStyle('blue'),
                ),
                $styles
            )
        );
    }
}
