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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDockerComposeCommand extends Command
{
    protected static $defaultName = 'create:docker-compose';
    protected static $defaultDescription = 'Membuat docker-compose config file';

    private $recreate = false;

    protected function configure()
    {
        $this->addArgument('app', InputArgument::OPTIONAL, 'Set app yang akan dibuat docker-compose config, jika kosong maka semua app pada config akan dibuat');
        $this->addOption('create-app', null, InputOption::VALUE_NONE, 'Recreate docker-compose config jika telah ada.');
        $this->addOption('recreate', null, InputOption::VALUE_NONE, 'Recreate docker-compose config jika telah ada.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $input->getArgument('app');
        $this->recreate = $input->getOption('recreate');
        $createAppCommand = $this->getApplication()->find('create:app');
        $inputCreateApp = new ArrayInput([
            'app' => $app,
            '--recreate' => $this->recreate,
        ]);
        $createAppCommand->run($inputCreateApp, $output);

        return self::SUCCESS;
    }
}
