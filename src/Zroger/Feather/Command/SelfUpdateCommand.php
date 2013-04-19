<?php

namespace Zroger\Feather\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Zroger\Feather\Phar\UpdateManager;
use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use KevinGH\Version\Version;

class SelfUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Update to the latest version of Feather.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manifest = Manifest::loadFile('http://zroger.github.io/feather/manifest.json');
        $manager = new UpdateManager($manifest);

        $version = $this->getApplication()->getVersion();
        if ($update = $manager->update($version, true)) {
            $output->writeln(sprintf('<info>Updated to version %s</info>', $update->getVersion()));
        }
        else {
            $output->writeln("<notice>No updates found.</notice>");
        }
    }

    public function isEnabled() {
        $phar_file = \Phar::running();
        return !empty($phar_file);
    }
}
