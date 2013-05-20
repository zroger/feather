<?php

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
