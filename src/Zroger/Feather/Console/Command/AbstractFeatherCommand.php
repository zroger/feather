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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractFeatherCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->addOption(
                'config',
                '-c',
                InputOption::VALUE_REQUIRED,
                'Specify an alternate configuration file to load.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $feather = $this->getApplication()->getFeather();

        // Load user config from ~/.feather.yml
        $file = $feather['home'] . '/.feather.yml';
        if (file_exists($file)) {
            $feather->loadYamlFile($file);
        }

        if ($config = $input->getOption('config')) {
            if (!file_exists($config)) {
                $feather['logger']->warning(sprintf('Config file %s not found.', $config));
                $config = false;
            }
        } else {
            $config = $feather['cwd'] . '/feather.yml';
        }

        if (file_exists($config)) {
            $feather->loadYamlFile($config);
        }
    }
}
