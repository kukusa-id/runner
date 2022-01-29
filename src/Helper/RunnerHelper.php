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

namespace Kukusa\Runner\Helper;

class RunnerHelper
{
    public static function generateRandomBytes($length = 16)
    {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
            print_r($e);
            exit;
        }
    }

    public static function random_string($count): string
    {
        $seed = str_split('abcdefghijklmnopqrstuvwxyz'
            .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            .'0123456789!@$%&*()'); // and any other characters
        shuffle($seed); // probably optional since array_is randomized; this may be redundant
        $rand = '';
        foreach (array_rand($seed, $count) as $k) {
            $rand .= $seed[$k];
        }

        return $rand;
    }

    public static function envFileToArray($envFile): array
    {
        if (!file_exists($envFile)) {
            return [];
        }
        preg_match_all("/\n([a-zA-Z0-9_]+)=(.*)/", "\n".file_get_contents($envFile), $matches);
        if (!$matches) {
            return [];
        }
        $envs = [];
        foreach ($matches[1] as $index => $key) {
            $envs[$key] = isset($matches[2]) && isset($matches[2][$index]) ? trim($matches[2][$index]) : '';
        }

        return $envs;
    }

    public static function addEnvValueInFile($envFile, $key, $newValue, $replace = true)
    {
        if (!file_exists($envFile)) {
            return;
        }
        $fileContent = file_get_contents($envFile);
        preg_match("/\n$key=(.*)/", "\n".$fileContent, $matches);
        if (!$matches) {
            file_put_contents($envFile, "\n$key=$newValue", \FILE_APPEND);
        } else {
            if ($replace) {
                if ($matches[1] !== $newValue) {
                    $new = str_replace(ltrim($matches[0]), "$key=$newValue", $fileContent);
                    file_put_contents($envFile, $new);
                }
            }
        }
    }
}
