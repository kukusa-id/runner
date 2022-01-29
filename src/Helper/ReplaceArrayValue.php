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

class ReplaceArrayValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function __set_state($state)
    {
        if (!isset($state['value'])) {
            throw new \Exception('Failed to instantiate class "ReplaceArrayValue". Required parameter "value" is missing');
        }

        return new self($state['value']);
    }
}
