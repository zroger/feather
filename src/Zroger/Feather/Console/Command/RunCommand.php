<?php

namespace Zroger\Feather\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected $error_log;
    protected $root;
    protected $container;

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

        $this->apache = $this->getApplication()->getContainer()->get('feather');

        if (!$this->apache->start()) {
            throw new \RuntimeException('Error starting server.');
        }
        $port = $this->apache->getPort();
        $this->getApplication()->log("Listening on localhost:{$port}, CTRL+C to stop.", 'info');

        $file = $this->apache->getConfigFile();
        $this->getApplication()->log(sprintf('Using config file: %s', $file), 'debug');

        $app = $this->getApplication();
        $this->getApplication()->getContainer()->get('log_watcher')->watch(
            function ($label, $line) use ($app) {
                $app->log($line->getMessage(), $line->getLevel());
            }
        );
    }

    public function shutdown()
    {
        // The extra log is to get a clean line after a ^C
        printf("\r");
        // $this->getApplication()->log('');
        $this->getApplication()->log('Shutting down...', 'info');
        if ($this->apache->stop()) {
            $this->getApplication()->log('Server successfully stopped.', 'info');
            exit();
        } else {
            $this->getApplication()->log('Error stopping server.', 'error');
            exit(1);
        }

    }
}
