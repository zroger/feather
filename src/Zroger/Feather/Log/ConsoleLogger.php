<?php

namespace Zroger\Feather\Log;

use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Zroger\Feather\Console\Formatter\OutputFormatter;

class ConsoleLogger extends AbstractLogger
{
    /**
     * The console output instance.
     * @var OutputInterface
     */
    protected $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
        $this->output->setFormatter(new OutputFormatter($this->output->isDecorated()));
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $line = sprintf('<%s>[%s]</%s> %s', $level, $level, $level, strtr($message, $context));
        $this->output->writeln($line);
    }
}
