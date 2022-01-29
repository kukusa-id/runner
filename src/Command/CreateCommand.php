<?php

/*
 * This file is part of the Kukusa project.
 *
 * (c) Upik Saleh <upxsal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Kukusa\Runner\Command;

use Kukusa\Runner\Command;
use Kukusa\Runner\ConfigObject\AppItemConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class CreateCommand extends Command
{
    protected static $defaultName = 'create';
    private bool $recreate;

    protected function configure()
    {
        $this->addArgument('app', InputArgument::OPTIONAL, 'Set app yang akan dibuat, jika kosong maka semua app pada config akan dibuat');
        $this->addOption('recreate', null, InputOption::VALUE_NONE, 'Recreate app jika telah ada.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->recreate = $input->getOption('recreate');
        $config = $this->getConfig();
        $output->writeln('<bg=cyan;options=bold>Membuat apps...</>');
        foreach ($config->apps as $appName => $appConfig) {
            $mode = $appConfig->mode;
            $output->writeln(':'.$appName);
            if (AppItemConfig::MODE_LOCAL === $mode) {
                $this->createLocalApp($appConfig);
            }
        }

        return self::SUCCESS;
    }

    public function createLocalApp(AppItemConfig $appConfig)
    {
        $fs = new Filesystem();
        $templatePath = $appConfig->getTemplatePath();
        $appRunDir = $appConfig->getRunDir();

        if (is_dir($appRunDir)) {
            if (!$this->recreate) {
                $this->output->writeln('::<bg=yellow;fg=black;>(skip) Telah ada</>');

                return;
            }

            $this->output->writeln('::<bg=yellow;fg=black;>(Recreate)</>');
            $finder = $appConfig->getTypeClass()->finderOnDeleteApp($appRunDir);
            if ($finder->hasResults()) {
                foreach ($finder->files() as $file) {
                    $fs->remove($file->getRealPath());
                }
            }
        }
        $this->output->writeln('::Menyalin template...');

        $finder = $appConfig->getTypeClass()->finderOnCreate($templatePath);
        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            $fileSimplePath = str_replace($templatePath, '', $file->getRealPath());
            $toAppRunDir = $appRunDir.$fileSimplePath;
            if (is_dir($file->getRealPath()) && !is_dir($toAppRunDir)) {
                $fs->mkdir($toAppRunDir, $file->getPerms());
            }
            if (is_file($file->getRealPath())) {
                $fs->copy($file->getRealPath(), $toAppRunDir);
            }
        }
    }
}
