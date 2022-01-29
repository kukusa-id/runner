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

namespace Kukusa\Runner\ConfigObject;

use Kukusa\Runner\BaseConfigObject;
use Kukusa\Runner\Helper\ArrayHelper;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Yaml\Yaml;
use function Symfony\Component\String\u;

class KukusaConfig extends BaseConfigObject
{
    public string $runDir;
    public string $env;
    /**
     * runner project name.
     */
    public string $name;
    public string $hostname;

    /**
     * list app items.
     *
     * @var AppItemConfig[]
     */
    public array $apps;

    /**
     * path kukusa workspace.
     */
    public string|null $workspacePath = null;
    /**
     * set path templates, jika workspace path tidak di set.
     */
    public string|null $templatesPathLocal = null;

    public array $dockerComposeMerge = [];

    // =========================================================================
    protected function _normalize(&$config): void
    {
        if (!isset($config['name'])) {
            throw new RuntimeException('config key "name" harus di set.');
        }
        if (!isset($config['hostname'])) {
            throw new RuntimeException('config key "hostname" harus di set.');
        }
        $this->env = $config['env'];
        $this->runDir = $config['run_dir'];
        $defaultAppItemConfig = Yaml::parseFile(__DIR__.'/../default-app-item-type.yaml') ?? [];
        $defaultAppItemConfigEnvs = [];
        foreach ($defaultAppItemConfig as $key => $value) {
            if (u($key)->startsWith('_')) {
                $defaultAppItemConfigEnvs[substr($key, 1, \strlen($key))] = ArrayHelper::remove($defaultAppItemConfig, $key, []);
            }
        }
        $defaultAppItemConfig = ArrayHelper::merge($defaultAppItemConfig, $defaultAppItemConfigEnvs[$this->env] ?? []);

        foreach ($config as $key => $value) {
            if ('apps' === $key) {
                foreach ($config['apps'] as $appName => $appConfig) {
                    unset($config['apps'][$appName]);
                    $appName = $appConfig['name'] = $appConfig['name'] ?? $appName;
                    if (!isset($appConfig['type'])) {
                        throw new RuntimeException("key \"type\" harus di set pada app \"$appName\"");
                    }
                    $appType = $appConfig['type'];
                    $appConfig = ArrayHelper::merge($defaultAppItemConfig[$appType] ?? [], $appConfig);
                    $appConfig['_root'] = $config;
                    $this->_replacingValueTemplate($appConfig, $appConfig);
                    $config['apps'][$appName] = $appConfig;
                }
            }
        }
        foreach ($config['apps'] as $appName => $appConfig) {
            $appConfig['_root'] = $this;
            $config['apps'][$appName] = new AppItemConfig($appConfig);
        }
    }

    private function _replacingValueTemplate(&$config, $baseConfig)
    {
        $match_callback = function ($value) use ($baseConfig) {
            return ArrayHelper::getValue($baseConfig, $value[1], $value[0]);
        };
        $config = array_map(function ($value) use ($match_callback, $baseConfig) {
            if (\is_array($value)) {
                $this->_replacingValueTemplate($value, $baseConfig);
            } elseif (\is_string($value)) {
                $value = preg_replace_callback('/{\%(.+?)\%}/m', $match_callback, $value);
            }

            return $value;
        }, $config);
    }

    // =========================================================================
    public function isLocalWorkspace(): bool
    {
        return null !== $this->workspacePath;
    }

    public function isEnvDev(): bool
    {
        return !$this->isEnvProd();
    }

    public function isEnvProd(): bool
    {
        return 'prod' === $this->env;
    }

    public function getRunDirApps(): string
    {
        return $this->runDir.'/apps';
    }

    public function getWorkspaceSourceDir(): string
    {
        return $this->workspacePath.'/sources';
    }
}
