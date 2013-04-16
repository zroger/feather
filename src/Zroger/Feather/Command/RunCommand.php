<?php

namespace Zroger\Feather\Command;

use Zroger\Feather\Apache;
use Zroger\Feather\ApacheConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Zroger\Feather\Config\AppConfig;

class RunCommand extends Command
{
    private $error_log, $root;

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
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_REQUIRED,
                'Start the server on a specific port.'
            )
        ;
    }

    protected function getConfig() {
        if (!isset($this->config)) {
            $this->config = new ApacheConfig($this->getApplication()->getServerRoot(), $this->getApplication()->getConfig());
        }
        return $this->config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Collect the CLI args and options into a config array.
        $cli_config = array();
        if ($port = $input->getOption('port')) {
            $cli_config['port'] = $port;
        }
        if ($root = $input->getArgument('root')) {
            $cli_config['root'] = $root;
        }
        $this->getApplication()->setConfig($cli_config);

        // catch app interruption
        if (function_exists('pcntl_signal')) {
            declare(ticks = 1);
            pcntl_signal(SIGINT, array($this, 'shutdown'));
        }

        $appconfig = $this->getApplication()->getConfig();
        $this->apache = new Apache($this->getApplication()->getServerRoot(), $appconfig['log_level']);

        // Open error log pipe before starting apache.
        $this->getConfig()->getErrorLog()->getHandle();

        $this->getApplication()->log('Starting server...', 'info');
        if (!$this->apache->start()) {
            print_r($this->getApplication()->getConfig());
            throw new \RuntimeException('Error starting server.');
        }
        $port = $this->getConfig()->getPort();
        $this->getApplication()->log("Listening on 0.0.0.0:{$port}, CTRL+C to stop.", 'info');
        $this->getApplication()->log(sprintf('Using config file: %s', $this->getConfig()->toFile()), 'info');

        while (TRUE) {
            while ($line = $this->getConfig()->getErrorLog()->read()) {
                $this->getApplication()->log($line->message, $line->type);
            }
            usleep(200);
        }
    }

    public function shutdown() {
        // The extra log is to get a clean line after a ^C
        $this->getApplication()->log('');
        $this->getApplication()->log('Shutting down...', 'info');
        $this->apache->stop();
        $this->getApplication()->log('Server successfully stopped.', 'info');

        exit();
    }
}
