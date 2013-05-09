<?php

namespace Zroger\Feather\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zroger\Feather\Log\FileReader;

class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run the server')
            ->addArgument(
                'root',
                InputArgument::OPTIONAL,
                'What directory should be used for the document root?'
            )
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'Specify a config file.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // catch app interruption
        if (function_exists('pcntl_signal')) {
            declare(ticks = 1);
            pcntl_signal(SIGINT, array($this, 'shutdown'));
        }

        $feather = $this->get('feather');
        $feather->start();

        $this->get('log_watcher')
            ->addReader('error_log', new FileReader($feather->getErrorLog()))
            ->addReader('access_log', new FileReader($feather->getAccessLog()))
            ->watch(
                function ($label, $line) use ($feather) {
                    $feather->getLogger()->log($line->getLevel(), $line->getMessage());
                }
            );
    }

    public function shutdown($signal)
    {
        if ($signal === SIGINT) {
            // Get a clean line after a ^C
            printf("\n");
        }

        $feather = $this->get('feather')->stop();
        exit();
    }

    protected function get($serviceName)
    {
        return $this->getApplication()->getContainer()->get($serviceName);
    }
}
