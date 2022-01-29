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

use Kukusa\Runner\Application;

require_once __DIR__.'/vendor/autoload.php';

$application = new Application();
$application->run();

//use Symfony\Component\Config\Definition\Processor;
//use Symfony\Component\Console\Application;
//use Symfony\Component\Yaml\Yaml;
//
//require __DIR__.'/vendor/autoload.php';
//$config = Yaml::parse(
//    file_get_contents(__DIR__.'/kukusa.yaml')
//);
//
//$configs = [$config];
//
//$processor = new Processor();
//$databaseConfiguration = new \Kukusa\Runner\Configuration\RootConfiguration();
//$processedConfiguration = $processor->processConfiguration(
//    $databaseConfiguration,
//    $configs
//);
//print_r($processedConfiguration);
////$app= new Application();
////$app->add(new RunCommand());
////$app->add(new CreateCommand());
////$app->add(new DownCommand());
////$app->add(new BuildCommand());
////$app->run(null, new ConsoleOutput());
