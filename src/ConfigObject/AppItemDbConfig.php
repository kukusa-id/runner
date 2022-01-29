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

class AppItemDbConfig extends BaseConfigObject
{
    use DockerComposeItemConfigTrait;
    private AppItemConfig $_app;
//    public bool $enabled = true;
    public string $platform = 'mysql';
    public string $host;
    public string|int $port;
    public string $username;
    public string $password;
    public string $dbname;
    public string $queryUrl = '?';

    protected function _normalize(&$config): void
    {
        if (isset($config['_app'])) {
            $this->_app = $config['_app'];
        }
    }

    // =========================================================================
    public function getDatabaseUrl()
    {
        return $this->platform.'://'.$this->username.':'.$this->password.'@'.$this->host.':'.$this->port.'/'.$this->dbname.$this->queryUrl;
    }
}
