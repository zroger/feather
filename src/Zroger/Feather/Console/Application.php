<?php

namespace Zroger\Feather\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use Zroger\Feather\Feather;
use Zroger\Feather\Config\AppConfig;
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
        $this->add(new Command\SelfUpdateCommand());
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
        $this->getFeather()->setLogger(new ConsoleLogger());

        // The inputs have not been processes at this point so it's necessary
        // to use the raw parameter values using getParameterOption instead of
        // getOption.
        if ($port = $input->getParameterOption(array('--port', '-p'))) {
            $this->getFeather()->setPort(intval($port));
        }
        if ($root = $input->getParameterOption(array('--root', '-r'))) {
            $this->getFeather()->setDocumentRoot(realpath($root));
        }

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

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $config = new AppConfig();
        $builder = $config->getConfigTreeBuilder();
        $tree = $builder->buildTree();
        $nodes = $tree->getChildren();

        foreach (array('document_root', 'port') as $key) {
            $node = $nodes[$key];
            $option = new InputOption(
                $node->getName(),
                null,
                InputOption::VALUE_REQUIRED,
                $node->getInfo(),
                $node->getDefaultValue()
            );
            $definition->addOption($option);
        }

        return $definition;
    }
}
