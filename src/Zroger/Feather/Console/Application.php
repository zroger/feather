<?php

/*
 * This file is part of the Feather package.
 *
 * (c) Roger López <roger@zroger.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zroger\Feather\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use KevinGH\Amend\Command as AmendCommand;
use KevinGH\Amend\Helper as AmendHelper;

use Zroger\Feather\Feather;
use Zroger\Feather\Console\Formatter\OutputFormatter;
use Zroger\Feather\Log\ConsoleLogger;

class Application extends BaseApplication
{
    const VERSION = "@git_version@";

    /**
     * The common feather instance.
     * @var Feather
     */
    protected $feather;

    public function __construct()
    {
        parent::__construct('Feather', self::VERSION);
        $this->add(new Command\RunCommand());

        if (\Phar::running()) {
            $command = new AmendCommand('self-update');
            $command->setManifestUri('http://zroger.github.io/feather/manifest.json');

            $this->getHelperSet()->set(new AmendHelper());
            $this->add($command);
        }
    }

    /**
     * Method overridden to add output customizations and use the output object
     * for application logging.
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $output) {
            $output = new ConsoleOutput();
            $output->setFormatter(new OutputFormatter($output->isDecorated()));
        }

        return parent::run($input, $output);
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $feather = $this->getFeather();

        $feather['logger'] = new ConsoleLogger();

        parent::doRun($input, $output);
    }

    /**
     * Get a Feather instance.
     *
     * @return Feather A Feather instance.
     */
    public function getFeather()
    {
        if (!isset($this->feather)) {
            $this->feather = new Feather();
        }

        return $this->feather;
    }

    // /**
    //  * Gets the default input definition.
    //  *
    //  * @return InputDefinition An InputDefinition instance
    //  */
    // protected function getDefaultInputDefinition()
    // {
    //     $definition = parent::getDefaultInputDefinition();

    //     $definition->addOptions(
    //         new InputOption(
    //             'document_root',
    //             null,
    //             InputOption::VALUE_REQUIRED,
    //             'The path to the document root.  Relative paths will be resolved to the current working directory.',
    //             './'
    //         ),
    //         new InputOption(
    //             'port',
    //             null,
    //             InputOption::VALUE_REQUIRED,
    //             'The port number for apache to listen on.',
    //             '8080'
    //         )
    //     );

    //     return $definition;
    // }
}
