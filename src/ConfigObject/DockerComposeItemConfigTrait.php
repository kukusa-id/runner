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

trait DockerComposeItemConfigTrait
{
    public DockerComposeItemConfig $dockerCompose;

    /**
     * @param DockerComposeItemConfig $dockerCompose
     */
    public function setDockerCompose(mixed $dockerCompose): void
    {
        if (\is_array($dockerCompose)) {
            if ($this instanceof AppItemConfig) {
                $dockerCompose['_app'] = $this;
            }
            $this->dockerCompose = new DockerComposeItemConfig($dockerCompose);
        }
    }
}
