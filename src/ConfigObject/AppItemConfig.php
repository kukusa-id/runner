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
use Kukusa\Runner\Types\AbstractType;
use Symfony\Component\Console\Exception\RuntimeException;

class AppItemConfig extends BaseConfigObject
{
    use DockerComposeConfigTrait;
    use DockerComposeItemConfigTrait;
    public const MODE_LOCAL = 'local';
    private KukusaConfig $_root;
    private AbstractType $_typeClass;
    public string $name;
    public string $type;
    public string $hostname;
    public bool $enabled = true;
    public string $mode = self::MODE_LOCAL;
    public string $templateName = 'app';
    public AppItemDbConfig $db;
    public AppItemGatewayConfig $gateway;

    public bool $isBundleApp = false;
    public $other = [];

    // =========================================================================
    protected function _normalize(&$config): void
    {
        if (isset($config['_root'])) {
            $this->_root = $config['_root'];
        }
        foreach ($config as $key => $value) {
            if ('db' === $key) {
                $value['_app'] = $this;
                $config['db'] = new AppItemDbConfig($value);
            } elseif ('gateway' === $key) {
                $value['_app'] = $this;
                $config['gateway'] = new AppItemGatewayConfig($value);
            }
        }
    }

    public function setType(string $type): void
    {
        $typeClass = '\\Kukusa\\Runner\\Types\\'.$type;
        if (!class_exists($typeClass)) {
            throw new RuntimeException("Type class tidak ditemukan \"$typeClass\"");
        }
        $this->_typeClass = new $typeClass($this);
        $this->type = $type;
    }

    // =========================================================================
    public function getRoot(): KukusaConfig
    {
        return $this->_root;
    }

    public function getTemplatePath()
    {
        if (self::MODE_LOCAL === $this->mode) {
            if ($this->_root->templatesPathLocal) {
                $templatePath = $this->_root->templatesPathLocal.'/'.$this->templateName;
            } elseif ($this->_root->isLocalWorkspace()) {
                $templatePath = $this->_root->workspacePath.'/templates/'.$this->templateName;
            } else {
                throw new RuntimeException('Template path salah.');
            }
            if (!is_dir($templatePath)) {
                throw new RuntimeException("Template path \"$templatePath\" tidak ditemukan.");
            }

            return $templatePath;
        }
    }

    public function getRunDir(): string
    {
        return $this->_root->getRunDirApps().'/'.$this->name;
    }

    public function getTypeClass()
    {
        return $this->_typeClass;
    }
}
