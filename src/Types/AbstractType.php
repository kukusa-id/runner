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

use Kukusa\Runner\ConfigObject\AppItemConfig;
use Symfony\Component\Finder\Finder;

abstract class AbstractType
{
    protected AppItemConfig $config;

    public function __construct(AppItemConfig $appConfig)
    {
        $this->config = $appConfig;
    }

    abstract public function finderOnCreate($templatePath): Finder;

    abstract public function finderOnDeleteApp($appRunDir): Finder;

    abstract public function dockerComposeConfigure();

    abstract public function updateApp();
}
