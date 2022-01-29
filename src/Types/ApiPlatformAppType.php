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

namespace Kukusa\Runner\Types;

use Kukusa\Runner\Helper\ArrayHelper;
use Kukusa\Runner\Helper\RunnerHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ApiPlatformAppType extends AbstractType
{
    public function finderOnCreate($templatePath): Finder
    {
        return (new Finder())
            ->in($templatePath)
            ->ignoreDotFiles(false)
            ->notName(['.env.local', '*env.*.local']);
    }

    public function finderOnDeleteApp($appRunDir): Finder
    {
        return (new Finder())
            ->in($appRunDir)
            ->notPath(['.runner', 'config/local', 'config/jwt', 'public/bundles'])
            ->notName(['.env.local', '.env.*.local'])
            ->ignoreDotFiles(false);
    }

    public function dockerComposeConfigure()
    {
        $appConfig = $this->config;
        $serviceName = $appConfig->name;
        $appRunDir = $appConfig->getRunDir();
        $fs = new Filesystem();
        $appRunDirRelative = './'.rtrim($fs->makePathRelative($appRunDir, $appConfig->getRoot()->runDir), '/');
        $env = $appConfig->getRoot()->env;
        $volumes = [
            "$appRunDirRelative/.env:/srv/api/.env",
            "$appRunDirRelative/composer.json:/srv/api/composer.json",
            "$appRunDirRelative/composer.lock:/srv/api/composer.lock",
            "$appRunDirRelative/symfony.lock:/srv/api/symfony.lock",
            "$appRunDirRelative/public:/srv/api/public",
            "$appRunDirRelative/bin:/srv/api/bin",
            "$appRunDirRelative/config:/srv/api/config",
            "$appRunDirRelative/migrations:/srv/api/migrations",
            "$appRunDirRelative/fixtures:/srv/api/fixtures",
            "$appRunDirRelative/src:/srv/api/src",
            "$appRunDirRelative/templates:/srv/api/templates",
            "$appRunDirRelative/.runner/$env/php/docker-entrypoint.sh:/usr/local/bin/docker-entrypoint",
            "$appRunDirRelative/.runner/$env/php/php.ini:/usr/local/etc/php/conf.d/kukusa-api.ini",
            "{$serviceName}_run:/var/run/php",
            "{$serviceName}_var:/srv/api/var",
            "{$serviceName}_vendor:/srv/api/vendor",
        ];
        if (is_file("$appRunDir/.env.$env")) {
            $volumes[] = "$appRunDirRelative/.env.$env:/srv/api/.env.$env";
        }
        if (is_file("$appRunDir/.env.local")) {
            $volumes[] = "$appRunDirRelative/.env.local:/srv/api/.env.local";
        }
        if (is_file("$appRunDir/.env.$env.local")) {
            $volumes[] = "$appRunDirRelative/.env.$env.local:/srv/api/.env.$env.local";
        }
        if ($appConfig->getRoot()->isLocalWorkspace()) {
            $volumes[] = $appConfig->getRoot()->getWorkspaceSourceDir().':/srv/api/kukusa';
        }

        $appConfig->dockerComposeConfigMerge([
            'volumes' => [
                $serviceName.'_run' => ['external' => false],
                $serviceName.'_var' => ['external' => false],
                $serviceName.'_vendor' => ['external' => false],
            ],
            'services' => [
                $serviceName => [
                    'volumes' => $volumes,
                ],
            ],
        ]);
        if ($appConfig->gateway->dockerCompose->enabled) {
            if ('caddy' === $appConfig->gateway->platform) {
                $caddyEnvFile = "$appRunDir/.runner/$env/caddy/.env";
                if (!file_exists($caddyEnvFile)) {
                    file_put_contents($caddyEnvFile, "# caddy environment local $env\n\n");
                }
                RunnerHelper::addEnvValueInFile($caddyEnvFile, 'MERCURE_PUBLISHER_JWT_KEY', RunnerHelper::generateRandomBytes(), false);
                RunnerHelper::addEnvValueInFile($caddyEnvFile, 'MERCURE_SUBSCRIBER_JWT_KEY', RunnerHelper::generateRandomBytes(), false);
                $appConfig->dockerComposeConfigMergeSet("services.{$serviceName}_gw.volumes", [
                    "{$serviceName}_run:/var/run/php",
                    "$appRunDirRelative/public:/srv/api/public",
                    "$appRunDirRelative/.runner/$env/caddy/Caddyfile:/etc/caddy/Caddyfile",
                    "$appRunDirRelative/.runner/$env/caddy/data:/data",
                    "$appRunDirRelative/.runner/$env/caddy/config:/config",
                ]);

                $appConfig->dockerComposeConfigMergeSet("services.{$serviceName}_gw.environment.SERVER_NAME", $appConfig->hostname.':80');
                $appConfig->dockerComposeConfigMergeSet("services.{$serviceName}_gw.env_file", [
                    "$appRunDirRelative/.runner/$env/caddy/.env",
                ]);
            }
        }
    }

    public function updateApp()
    {
        $appConfig = $this->config;
        $appRunDir = $appConfig->getRunDir();
        $env = $appConfig->getRoot()->env;

        $envLocalEnvFile = "$appRunDir/.env.$env.local";
        if (!file_exists($envLocalEnvFile)) {
            file_put_contents($envLocalEnvFile, "# environment local $env\n\n");
        }
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'APP_ENV', $env, true);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'APP_SECRET', RunnerHelper::random_string(12), false);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'TRUSTED_PROXIES', '127.0.0.0/8,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16', false);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'TRUSTED_HOSTS', '', true);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'DATABASE_URL', $appConfig->db->getDatabaseUrl(), true);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'CORS_ALLOW_ORIGIN', '', true);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'MERCURE_URL', '', true);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'MERCURE_PUBLIC_URL', '', true);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'MERCURE_JWT_SECRET', RunnerHelper::random_string(12), false);
        RunnerHelper::addEnvValueInFile($envLocalEnvFile, 'JWT_PASSPHRASE', RunnerHelper::generateRandomBytes(), false);
        if ($appConfig->getRoot()->isLocalWorkspace()) {
            // composer.json
            $composerFileArray = json_decode(file_get_contents($appRunDir.'/composer.json'), true);
            ArrayHelper::setValue($composerFileArray, 'autoload.psr-4.Kukusa\\Core\\', 'kukusa/core/src');
            file_put_contents($appRunDir.'/composer.json', json_encode($composerFileArray, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        }
    }
}
