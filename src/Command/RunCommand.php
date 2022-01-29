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
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected static $defaultName = 'run';

    protected function configure()
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'Run type ex: dc');
        $this->addOption('no-recreate', null, InputOption::VALUE_NONE, 'Jangan Recreate app jika telah ada.');
        $this->addOption('no-reupdate', null, InputOption::VALUE_NONE, 'Jangan Reupdate app.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // update command
        $updateCommand = $this->getApplication()->find('update');
        $updateCommandInput = [];
        if (!$input->getOption('no-reupdate')) {
            $updateCommandInput['--recreate'] = !$input->getOption('no-recreate');
            $updateCommand->run(new ArrayInput($updateCommandInput, $updateCommand->getDefinition()), $output);
        }
        $config = $this->getConfig();
        $runType = $input->getArgument('type');
        if ('dc' === $runType) {
            $dockerComposeRunFile = "{$config->runDir}/docker-compose.{$config->env}.yaml";
            if (!is_file($dockerComposeRunFile)) {
                throw new RuntimeException('Docker compose file config tidak ada atau belum di update.');
            }
            system("docker-compose -p '$config->name' -f {$dockerComposeRunFile} up -d");
        }

        return self::SUCCESS;
    }
}
