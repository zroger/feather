<?php

namespace Zroger\Feather\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $feather = $this->getApplication()->getContainer()->get('feather');

        if (!$feather->start()) {
            throw new \RuntimeException('Error starting server.');
        }
        $port = $feather->getPort();
        $feather->getLogger()->info("Listening on localhost:{$port}, CTRL+C to stop.");

        $file = $feather->getConfigFile();
        $feather->getLogger()->debug(sprintf('Using config file: %s', $file));

        $this->getApplication()->getContainer()->get('log_watcher')->watch(
            function ($label, $line) use ($feather) {
                $feather->getLogger()->log($line->getLevel(), $line->getMessage());
            }
        );
    }

    public function shutdown()
    {
        // The extra log is to get a clean line after a ^C
        printf("\r");
        $feather = $this->getApplication()->getContainer()->get('feather');

        $feather->getLogger()->info('Shutting down...');

        if ($feather->stop()) {
            $feather->getLogger()->info('Server successfully stopped.');
            exit();
        } else {
            $feather->getLogger()->error('Error stopping server.');
            exit(1);
        }
    }
}
