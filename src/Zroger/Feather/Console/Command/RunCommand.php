<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zroger\Feather\Log\FileReader;
use Zroger\Feather\Log\Watcher;

class RunCommand extends AbstractFeatherCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('run')
            ->setDescription('Run the server')
            ->addOption(
                'root',
                '-r',
                InputOption::VALUE_REQUIRED,
                'The path to the document root.  Relative paths will be resolved to the current working directory.',
                '.'
            )
            ->addOption(
                'port',
                '-p',
                InputOption::VALUE_REQUIRED,
                'The port number for apache to listen on.',
                '8080'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // catch app interruption
        if (function_exists('pcntl_signal')) {
            declare(ticks = 1);
            pcntl_signal(SIGINT, array($this, 'shutdown'));
        }

        $feather = $this->getApplication()->getFeather();

        if ($port = $input->getOption('port')) {
            $feather['port'] = intval($port);
        }

        if ($root = $input->getOption('root')) {
            $feather['document_root'] = realpath($root);
        }

        $feather->start();

        $watcher = new Watcher();
        $watcher
            ->addReader('error_log', new FileReader($feather['error_log']))
            ->addReader('access_log', new FileReader($feather['access_log']))
            ->watch(
                function ($label, $line) use ($feather) {
                    $feather['logger']->log($line->getLevel(), $line->getMessage());
                }
            );
    }

    /**
     * Shutdown the server and exit in response to a SIGINT.
     *
     * @param  int $signal The signal being handled.
     */
    public function shutdown($signal)
    {
        if ($signal === SIGINT) {
            // Get a clean line after a ^C
            printf("\n");
        }

        $this->getApplication()->getFeather()->stop();
        exit();
    }
}
