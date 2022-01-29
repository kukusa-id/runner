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
use Kukusa\Runner\Helper\ArrayHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class UpdateCommand extends Command
{
    protected static $defaultName = 'update';

    private bool $reupdate = false;

    protected function configure()
    {
        $this->addOption('recreate', null, InputOption::VALUE_NONE, 'Recreate app jika telah ada.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // create command
        $createCommand = $this->getApplication()->find('create');
        $createCommandInput = [];
        if ($input->getOption('recreate')) {
            $createCommandInput['--recreate'] = true;
            $createCommand->run(new ArrayInput($createCommandInput, $createCommand->getDefinition()), $output);
        }

        $config = $this->getConfig();

        $output->writeln('<bg=cyan;options=bold>Update apps...</>');
        $dockerComposeConfig = [];
        foreach ($config->apps as $appName => $appConfig) {
            $mode = $appConfig->mode;
            $output->writeln(':'.$appName);
            if (AppItemConfig::MODE_LOCAL === $mode) {
                $this->updateLocalApp($appConfig);
            }
            $this->updateApp($appConfig);
            $dockerComposeConfig = ArrayHelper::merge($dockerComposeConfig, $appConfig->getDockerComposeConfig());
        }
        // docker-compose global config
        file_put_contents($config->runDir.'/docker-compose.'.$config->env.'.yaml',
            Yaml::dump(ArrayHelper::merge($dockerComposeConfig, $config->dockerComposeMerge), Yaml::DUMP_OBJECT_AS_MAP));

        return self::SUCCESS;
    }

    private function updateLocalApp(AppItemConfig $appConfig)
    {
    }

    private function updateApp(AppItemConfig $appConfig)
    {
        //app docker compose
        $this->updateAppDockerCompose($appConfig);
        $appConfig->getTypeClass()->updateApp();
    }

    private function updateAppDockerCompose(AppItemConfig $appConfig)
    {
        $dcConfig = $appConfig->dockerCompose;
        if (!$dcConfig->enabled) {
            return;
        }
        $serviceName = $appConfig->name;
        $appConfig->dockerComposeConfigMerge([
            'services' => [
                $serviceName => ArrayHelper::merge([
                    'image' => $dcConfig->image,
                    'restart' => 'unless-stopped',
                ], $dcConfig->merge),
            ],
        ]);
        // DATABASE DOCKER COMPOSE
        if ($appConfig->db->dockerCompose->enabled) {
            $dcConfigDb = $appConfig->db->dockerCompose;
            $appConfig->dockerComposeConfigMerge([
                'services' => [
                    $serviceName.'_db' => ArrayHelper::merge([
                        'image' => $dcConfigDb->image,
                        'restart' => 'unless-stopped',
                    ], $dcConfigDb->merge),
                ],
            ]);
            $appConfig->dockerComposeConfigMergeSet('services.'.$serviceName.'.depends_on', [$serviceName.'_db']);
        }

        // GATEWAY DOCKER COMPOSE
        if ($appConfig->gateway->dockerCompose->enabled) {
            $dcConfigGateway = $appConfig->gateway->dockerCompose;
            $appConfig->dockerComposeConfigMerge([
                'services' => [
                    $serviceName.'_gw' => ArrayHelper::merge([
                        'image' => $dcConfigGateway->image,
                        'hostname' => $appConfig->hostname,
                        'restart' => 'unless-stopped',
                        'depends_on' => [$serviceName],
                    ], $dcConfigGateway->merge),
                ],
            ]);
        }
        $appConfig->getTypeClass()->dockerComposeConfigure();
//        file_put_contents($appConfig->getRoot()->getRunDirDockerCompose().'/docker-compose.'.$serviceName.'.'.$appConfig->getRoot()->env.'.yaml',
//            Yaml::dump($appConfig->getDockerComposeConfig(), Yaml::DUMP_OBJECT_AS_MAP));
    }
}
