<?php

namespace Zroger\Feather\Console\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatter as BaseOutputFormatter,
    Symfony\Component\Console\Formatter\OutputFormatterStyle;

class OutputFormatter extends BaseOutputFormatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct($decorated = null, array $styles = array())
    {
        parent::__construct($decorated, array_merge(array(
            'error'     => new OutputFormatterStyle('red'),
            'debug'     => new OutputFormatterStyle('yellow'),
            'notice'    => new OutputFormatterStyle('yellow'),
            'info'      => new OutputFormatterStyle('cyan'),
        ), $styles));
    }
}
