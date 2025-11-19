<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Adapter;

use Alchemy\Zippy\Adapter\BSDTar\TarBSDTarAdapter;
use Alchemy\Zippy\Adapter\BSDTar\TarBz2BSDTarAdapter;
use Alchemy\Zippy\Adapter\BSDTar\TarGzBSDTarAdapter;
use Alchemy\Zippy\Adapter\GNUTar\TarBz2GNUTarAdapter;
use Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter;
use Alchemy\Zippy\Adapter\GNUTar\TarGzGNUTarAdapter;
use Alchemy\Zippy\Resource\RequestMapper;
use Alchemy\Zippy\Resource\ResourceManager;
use Alchemy\Zippy\Resource\ResourceTeleporter;
use Alchemy\Zippy\Resource\TargetLocator;
use Alchemy\Zippy\Resource\TeleporterContainer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;

class AdapterContainer implements \ArrayAccess
{

    private array $items = array();

    /**
     * Builds the adapter container
     */
    public static function load(): AdapterContainer
    {
        $container = new static();

        $container['zip.inflator'] = null;
        $container['zip.deflator'] = null;

        $container['resource-manager'] = function($container) {
            return new ResourceManager(
                $container['request-mapper'],
                $container['resource-teleporter'],
                $container['filesystem']
            );
        };

        $container['executable-finder'] = function($container) {
            return new ExecutableFinder();
        };

        $container['request-mapper'] = function($container) {
            return new RequestMapper($container['target-locator']);
        };

        $container['target-locator'] = function() {
            return new TargetLocator();
        };

        $container['teleporter-container'] = function($container) {
            return TeleporterContainer::load();
        };

        $container['resource-teleporter'] = function($container) {
            return new ResourceTeleporter($container['teleporter-container']);
        };

        $container['filesystem'] = function() {
            return new Filesystem();
        };

        $container['Alchemy\\Zippy\\Adapter\\ZipAdapter'] = function($container) {
            return ZipAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['zip.inflator'],
                $container['zip.deflator']
            );
        };

        $container['gnu-tar.inflator'] = null;
        $container['gnu-tar.deflator'] = null;

        $container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarGNUTarAdapter'] = function($container) {
            return TarGNUTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['gnu-tar.inflator'],
                $container['gnu-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarGzGNUTarAdapter'] = function($container) {
            return TarGzGNUTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['gnu-tar.inflator'],
                $container['gnu-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarBz2GNUTarAdapter'] = function($container) {
            return TarBz2GNUTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['gnu-tar.inflator'],
                $container['gnu-tar.deflator']
            );
        };

        $container['bsd-tar.inflator'] = null;
        $container['bsd-tar.deflator'] = null;

        $container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarBSDTarAdapter'] = function($container) {
            return TarBSDTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['bsd-tar.inflator'],
                $container['bsd-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarGzBSDTarAdapter'] = function($container) {
            return TarGzBSDTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['bsd-tar.inflator'],
                $container['bsd-tar.deflator']
            );
        };

        $container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarBz2BSDTarAdapter'] = function($container) {
            return TarBz2BSDTarAdapter::newInstance(
                $container['executable-finder'],
                $container['resource-manager'],
                $container['bsd-tar.inflator'],
                $container['bsd-tar.deflator']);
        };

        $container['Alchemy\\Zippy\\Adapter\\ZipExtensionAdapter'] = function() {
            return ZipExtensionAdapter::newInstance();
        };

        return $container;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (array_key_exists($offset, $this->items) && is_callable($this->items[$offset])) {
            $this->items[$offset] = call_user_func($this->items[$offset], $this);
        }

        if (array_key_exists($offset, $this->items)) {
            return $this->items[$offset];
        }

        throw new \InvalidArgumentException();
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
