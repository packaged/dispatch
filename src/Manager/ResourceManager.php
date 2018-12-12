<?php
namespace Packaged\Dispatch\Manager;

use Packaged\Dispatch\Dispatch;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;

class ResourceManager
{
  const MAP_VENDOR = 'v';
  const MAP_ALIAS = 'a';
  const MAP_RESOURCES = 'r';

  protected $_type = self::MAP_RESOURCES;
  protected $_mapOptions = [];
  protected $_baseUri = [];

  public function __construct($type, array $options = [])
  {
    $this->_type = $type;
    $this->_mapOptions = $options;
    $this->_baseUri = array_merge([$type], $options);
  }

  public static function vendor($vendor, $package)
  {
    return new static(self::MAP_VENDOR, [$vendor, $package]);
  }

  public static function alias($alias)
  {
    return new static(self::MAP_ALIAS, [$alias]);
  }

  public static function resources()
  {
    return new static(self::MAP_RESOURCES, []);
  }

  /**
   * @param $relativeFullPath
   *
   * @return string|null
   * @throws \Exception
   */
  public function getResourceUri($relativeFullPath): ?string
  {
    if($this->isExternalUrl($relativeFullPath))
    {
      return $relativeFullPath;
    }
    $hash = $this->getFileHash($this->getFilePath($relativeFullPath));
    return Path::custom('/', array_merge($this->_baseUri, [$hash, $relativeFullPath]));
  }

  /**
   * @param $relativePath
   *
   * @return string
   * @throws \Exception
   */
  public function getFilePath($relativePath)
  {
    if($this->_type == self::MAP_RESOURCES)
    {
      return Path::system(Dispatch::instance()->getResourcesPath(), $relativePath);
    }
    else if($this->_type == self::MAP_VENDOR)
    {
      [$vendor, $package] = $this->_mapOptions;
      return Path::system(Dispatch::instance()->getVendorPath($vendor, $package), $relativePath);
    }
    else if($this->_type == self::MAP_ALIAS)
    {
      return Path::system(Dispatch::instance()->getAliasPath($this->_mapOptions[0]), $relativePath);
    }
    throw new \Exception("invalid map type");
  }

  protected function getFileHash($fullPath)
  {
    $key = 'pdspfh-' . md5($fullPath) . '-' . filectime($fullPath);

    if(function_exists("apcu_fetch"))
    {
      $exists = null;
      $hash = apcu_fetch($key, $exists);
      if($exists && $hash)
      {
        return $hash;
      }
    }

    $hash = substr(md5_file($fullPath), 0, 8);

    if($hash && function_exists('apcu_store'))
    {
      apcu_store($key, $hash, 86400);
    }

    return $hash;
  }

  /**
   * Detect if URL has a protocol
   *
   * @param string $path
   *
   * @return bool
   */
  public function isExternalUrl($path)
  {
    return strlen($path) > 8 && (
        Strings::startsWith($path, 'http://', true, 7) ||
        Strings::startsWith($path, 'https://', true, 8) ||
        Strings::startsWith($path, '//', true, 2)
      );
  }
}
