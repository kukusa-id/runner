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

use function Symfony\Component\String\u;

abstract class BaseConfigObject
{
    public function __construct(array $config)
    {
        $this->_normalize($config);
        $reflectionClass = new \ReflectionClass($this);
        foreach ($config as $key => $value) {
            if (u($key)->startsWith('_')) {
                continue;
            }

            $keyCamel = u($key)->camel()->toString();

            $setMethodName = 'set'.ucfirst($keyCamel);
            if ($reflectionClass->hasMethod($setMethodName)) {
                $this->{$setMethodName}($value);
                continue;
            }

            if ($reflectionClass->hasProperty($keyCamel) && $reflectionClass->getProperty($keyCamel)->isPublic()) {
                $this->{$keyCamel} = $value;
                continue;
            }
            if ($reflectionClass->hasProperty('other')) {
                $this->other[$keyCamel] = $value;
            }
        }
    }

    abstract protected function _normalize(&$config): void;
}
