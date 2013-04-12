<?php

namespace Zroger\Feather\Command;

use Zroger\Feather\ApacheConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;

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
            $this->config = new ApacheConfig();
        }
        return $this->config;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Yaml file is loaded first.
        if ($yaml = $this->findYamlFile()) {
            $this->getConfig()->loadYaml($yaml);
        }

        // Then CLI options.
        if ($port = $input->getOption('port')) {
            $this->getConfig()->setPort($port);
        }
        if ($root = $input->getArgument('root')) {
            $this->getConfig()->setRoot($root);
        }

        // catch app interruption
        if (function_exists('pcntl_signal')) {
            declare(ticks = 1);
            $this->output = $output;
            pcntl_signal(SIGINT, array($this, 'shutdown'));
        }

        $style = new OutputFormatterStyle('red', null, array('bold', 'blink'));
        $output->getFormatter()->setStyle('error', $style);

        $style = new OutputFormatterStyle('yellow', null, array('bold', 'blink'));
        $output->getFormatter()->setStyle('debug', $style);
        $output->getFormatter()->setStyle('notice', $style);

        $output->writeln('<info>Starting server...</info>');
        $process = new Process(sprintf('apachectl -f "%s" -k start -e debug', $this->getConfig()->toFile()));
        $process->start();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Error starting httpd.");
        }
        $port = $this->getConfig()->getPort();
        $output->writeln("<info>Listening on 0.0.0.0:{$port}, CTRL+C to stop.</info>");
        $output->writeln(sprintf('<info>Using config file: %s</info>', $this->getConfig()->toFile()));

        while (TRUE) {
            while ($line = $this->getConfig()->getErrorLog()->read()) {
                $output->writeln(sprintf('<%s>[%s]</%s> %s', $line->type, $line->type, $line->type, $line->message));
            }
            usleep(500);
        }
    }

    protected function findYamlFile() {
        $pwd = posix_getcwd();
        $conf = "$pwd/feather.yml";

        while (!(file_exists($conf))) {
            $pwd = dirname($pwd);
            $conf = "$pwd/feather.yml";
            if ($pwd == "/") {
                return false;
            }
        }

        return $conf;
    }

    public function shutdown() {
        // The new-line is because this normally happens after a ^C
        $this->output->writeln('');
        $this->output->writeln('<info>Shutting down...</info>');
        $process = new Process(sprintf('apachectl -f "%s" -k stop', $this->getConfig()->toFile()));
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Error stopping httpd.");
        }
        $this->output->writeln("<info>Server successfully stopped.</info>");

        exit();
    }
}
