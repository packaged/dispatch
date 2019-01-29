<?php
namespace Packaged\Dispatch;

use Packaged\Dispatch\Component\DispatchableComponent;
use Packaged\Dispatch\Component\FixedClassComponent;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;

class ResourceManager
{
  const MAP_INLINE = 'i';
  const MAP_VENDOR = 'v';
  const MAP_ALIAS = 'a';
  const MAP_RESOURCES = 'r';
  const MAP_PUBLIC = 'p';
  const MAP_COMPONENT = 'c';
  const MAP_EXTERNAL = 'e';

  protected $_type = self::MAP_RESOURCES;
  protected $_mapOptions = [];
  protected $_baseUri = [];
  /** @var DispatchableComponent */
  protected $_component;

  public function __construct($type, array $options = [])
  {
    $this->_type = $type;
    $this->_mapOptions = $options;
    $this->_baseUri = array_merge([$type], $options);
  }

  public function getMapType()
  {
    return $this->_type;
  }

  public function getMapOptions()
  {
    return $this->_mapOptions;
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

  public static function public()
  {
    return new static(self::MAP_PUBLIC, []);
  }

  public static function inline()
  {
    return new static(self::MAP_INLINE, []);
  }

  public static function external()
  {
    return new static(self::MAP_EXTERNAL, []);
  }

  public static function component(DispatchableComponent $component)
  {
    $dispatch = Dispatch::instance();
    if($component instanceof FixedClassComponent)
    {
      $class = $component->getComponentClass();
    }
    else
    {
      $class = get_class($component);
    }
    if($dispatch)
    {
      $maxPrefix = $maxAlias = '';
      $prefixLen = 0;
      foreach($dispatch->getComponentAliases() as $alias => $namespace)
      {
        $trimNs = ltrim($namespace, '\\');
        $len = strlen($trimNs);
        if(Strings::startsWith($class, $trimNs) && $len > $prefixLen)
        {
          $maxPrefix = $trimNs;
          $prefixLen = $len;
          $maxAlias = $alias;
        }
      }
      $class = str_replace($maxPrefix, $maxAlias, $class);
    }
    $parts = explode('\\', $class);
    array_unshift($parts, count($parts));
    $manager = new static(self::MAP_COMPONENT, $parts);
    $manager->_component = $component;
    return $manager;
  }

  /**
   * @param $relativeFullPath
   *
   * @return string|null
   * @throws \Exception
   */
  public function getResourceUri($relativeFullPath): ?string
  {
    if($this->_type == self::MAP_EXTERNAL || $this->isExternalUrl($relativeFullPath))
    {
      return $relativeFullPath;
    }
    $hash = $this->getFileHash($this->getFilePath($relativeFullPath));
    if(!$hash)
    {
      return null;
    }
    return Path::custom(
      '/',
      array_merge([Dispatch::instance()->getBaseUri()], $this->_baseUri, [$hash, $relativeFullPath])
    );
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
    else if($this->_type == self::MAP_PUBLIC)
    {
      return Path::system(Dispatch::instance()->getPublicPath(), $relativePath);
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
    else if($this->_type == self::MAP_COMPONENT && $this->_component)
    {
      return Path::system($this->_component->getResourceDirectory(), $relativePath);
    }
    throw new \Exception("invalid map type");
  }

  public function getFileHash($fullPath)
  {
    if(!file_exists($fullPath))
    {
      return null;
    }
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

  /**
   * Add js to the store
   *
   * @param string $toRequire filename, or JS if inline manager
   * @param        $options
   *
   * @return ResourceManager
   * @throws \Exception
   */
  public function requireJs($toRequire, $options = null)
  {
    if($this->_type == self::MAP_INLINE)
    {
      return $this->requireInlineJs($toRequire);
    }
    Dispatch::instance()->store()->requireJs($this->getResourceUri($toRequire), $options);
    return $this;
  }

  /**
   * Add a js script to the store
   *
   * @param $javascript
   *
   * @return ResourceManager
   */
  public function requireInlineJs($javascript)
  {
    Dispatch::instance()->store()->requireInlineJs($javascript);
    return $this;
  }

  /**
   * Add css to the store
   *
   * @param string $toRequire filename, or CSS if inline manager
   * @param        $options
   *
   * @return ResourceManager
   * @throws \Exception
   */
  public function requireCss($toRequire, $options = null)
  {
    if($this->_type == self::MAP_INLINE)
    {
      return $this->requireInlineCss($toRequire);
    }
    Dispatch::instance()->store()->requireCss($this->getResourceUri($toRequire), $options);
    return $this;
  }

  /**
   * Add css to the store
   *
   * @param $stylesheet
   *
   * @return ResourceManager
   */
  public function requireInlineCss($stylesheet)
  {
    Dispatch::instance()->store()->requireInlineCss($stylesheet);
    return $this;
  }
}
