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

use Kukusa\Runner\Helper\ArrayHelper;

trait DockerComposeConfigTrait
{
    private array $_dockerComposeConfig = [
        'version' => '3.8',
    ];

    public function dockerComposeConfigMerge($merge)
    {
        $this->_dockerComposeConfig = ArrayHelper::merge($this->_dockerComposeConfig, $merge);
    }

    public function dockerComposeConfigMergeSet($key, $value)
    {
        $current = ArrayHelper::getValue($this->_dockerComposeConfig, $key);
        if ($current && \is_array($value)) {
            $value = ArrayHelper::merge($current, $value);
        }
        ArrayHelper::setValue($this->_dockerComposeConfig, $key, $value);
    }

    public function getDockerComposeConfig(): array
    {
        return $this->_dockerComposeConfig;
    }

    public function addDockerComposeConfig($key, $value)
    {
    }
}
