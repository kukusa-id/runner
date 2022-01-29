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

namespace Kukusa\Runner;

use Kukusa\Runner\Command\CreateCommand;
use Kukusa\Runner\Command\CreateDockerComposeCommand;
use Kukusa\Runner\Command\RunCommand;
use Kukusa\Runner\Command\UpdateCommand;
use Kukusa\Runner\ConfigObject\KukusaConfig;
use Kukusa\Runner\Helper\ArrayHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    public const VERSION = '1.0.0';
    private string $runDir;
    private string $env;
    private KukusaConfig $config;

    public function __construct()
    {
        parent::__construct('Kukusa Runner', self::VERSION);
        $this->add(new RunCommand());
        $this->add(new CreateCommand());
        $this->add(new UpdateCommand());
        $this->add(new CreateDockerComposeCommand());
    }

    public function getConfig(): KukusaConfig
    {
        return $this->config;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $this->getDefinition()->addOption(new InputOption('path', 'p', InputArgument::OPTIONAL, 'Path dari run dir.', $_SERVER['PWD']));
        $this->getDefinition()->addOption(new InputOption('env', null, InputArgument::OPTIONAL, 'Environment dev|prod', 'dev'));

        return parent::run($input, $output);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->runDir = $input->getParameterOption('--path', $_SERVER['PWD']);
        $this->env = $input->getParameterOption('--env', 'dev');
        if (!is_dir($this->runDir)) {
            throw new RuntimeException('Direktori "'.$this->runDir.'" tidak ditemukan.');
        }
        if (!file_exists($this->runDir.'/kukusa.yaml')) {
            throw new RuntimeException('Tidak ditemukan kukusa.yaml pada direktori "'.$this->runDir.'" .');
        }

        $outputStyle = new OutputFormatterStyle(null, 'cyan', ['bold']);
        $output->getFormatter()->setStyle('infobold', $outputStyle);
        $this->initializeConfig();

        return parent::doRun($input, $output);
    }

    private function initializeConfig()
    {
        $config = Yaml::parse(
            file_get_contents($this->runDir.'/kukusa.yaml')
        );
        if (is_dir($this->runDir.'/config')) {
            foreach (glob($this->runDir.'/config/*.'.$this->env.'.yaml') as $configFile) {
                $configMerge = Yaml::parse(
                    file_get_contents($configFile)
                ) ?? [];
                $config = ArrayHelper::merge($config, $configMerge);
            }
        }
        $config['run_dir'] = realpath($this->runDir);
        $config['env'] = $this->env;
        $kukusaConfig = new KukusaConfig($config);
        $this->config = $kukusaConfig;
    }
}
