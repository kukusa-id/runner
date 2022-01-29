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

use Kukusa\Runner\ConfigObject\KukusaConfig;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    protected OutputInterface $output;

    public function getApplication(): ?Application
    {
        return parent::getApplication();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
    }

    public function getConfig(): KukusaConfig
    {
        return $this->getApplication()->getConfig();
    }

    public function getEnv()
    {
        return $this->getApplication()->getEnv();
    }
}
