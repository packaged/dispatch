<?php
namespace Packaged\Dispatch;

use Exception;
use Packaged\Dispatch\Component\DispatchableComponent;
use Packaged\Dispatch\Component\FixedClassComponent;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;
use RuntimeException;

class ResourceManager
{
  const MAP_INLINE = 'i';
  const MAP_VENDOR = 'v';
  const MAP_ALIAS = 'a';
  const MAP_RESOURCES = 'r';
  const MAP_PUBLIC = 'p';
  const MAP_COMPONENT = 'c';
  const MAP_EXTERNAL = 'e';

  const OPT_THROW_ON_FILE_NOT_FOUND = 'throw.file.not.found';

  protected $_type = self::MAP_RESOURCES;
  protected $_mapOptions = [];
  protected $_baseUri = [];
  protected $_componentPath;
  protected $_options = [];

  public function __construct($type, array $mapOptions = [], array $options = [])
  {
    $this->_type = $type;
    $this->_mapOptions = $mapOptions;
    $this->_options = $options;
    $this->_baseUri = array_merge([$type], $mapOptions);
  }

  public function setOption($option, $value)
  {
    $this->_options[$option] = $value;
    return $this;
  }

  public function getOption($option, $default = null)
  {
    return $this->_options[$option] ?? $default;
  }

  public function getMapType()
  {
    return $this->_type;
  }

  public function getMapOptions()
  {
    return $this->_mapOptions;
  }

  public static function vendor($vendor, $package, $options = [])
  {
    return new static(self::MAP_VENDOR, [$vendor, $package], $options);
  }

  public static function alias($alias, $options = [])
  {
    return new static(self::MAP_ALIAS, [$alias], $options);
  }

  public static function resources($options = [])
  {
    return new static(self::MAP_RESOURCES, [], $options);
  }

  public static function public($options = [])
  {
    return new static(self::MAP_PUBLIC, [], $options);
  }

  public static function inline($options = [])
  {
    return new static(self::MAP_INLINE, [], $options);
  }

  public static function external($options = [])
  {
    return new static(self::MAP_EXTERNAL, [], $options);
  }

  public static function component(DispatchableComponent $component, $options = [])
  {
    $fullClass = $component instanceof FixedClassComponent ? $component->getComponentClass() : get_class($component);
    return static::_componentManager($fullClass, Dispatch::instance(), $options);
  }

  public static function componentClass(string $componentClassName, $options = [])
  {
    return static::_componentManager($componentClassName, Dispatch::instance(), $options);
  }

  protected static function _componentManager($fullClass, Dispatch $dispatch = null, $options = []): ResourceManager
  {
    $class = ltrim($fullClass, '\\');
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

    $manager = new static(self::MAP_COMPONENT, $parts, $options);
    $manager->_componentPath = $dispatch->componentClassResourcePath($fullClass);
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

    $filePath = $this->getFilePath($relativeFullPath);
    $relHash = $this->getRelativeHash($filePath);
    $hash = $this->getFileHash($filePath);
    if(!$hash)
    {
      return null;
    }
    return Path::custom(
      '/',
      array_merge([Dispatch::instance()->getBaseUri()], $this->_baseUri, [$hash . $relHash, $relativeFullPath])
    );
  }

  public function getRelativeHash($filePath)
  {
    return Dispatch::instance()->generateHash(Dispatch::instance()->calculateRelativePath($filePath), 4);
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
    else if($this->_type == self::MAP_COMPONENT)
    {
      return Path::system($this->_componentPath, $relativePath);
    }
    throw new \Exception("invalid map type");
  }

  public function getFileHash($fullPath)
  {
    if(!file_exists($fullPath))
    {
      if($this->getOption(self::OPT_THROW_ON_FILE_NOT_FOUND, true))
      {
        throw new RuntimeException("Unable to find dispatch file '$fullPath'", 404);
      }
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

    $hash = Dispatch::instance()->generateHash(md5_file($fullPath), 8);
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
   * Add js to the store, ignoring exceptions
   *
   * @param string $toRequire filename, or JS if inline manager
   * @param        $options
   *
   * @return ResourceManager
   */
  public function includeJs($toRequire, $options = null)
  {
    try
    {
      return $this->requireJs($toRequire, $options);
    }
    catch(Exception $e)
    {
      return $this;
    }
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
      return $this->_requireInlineJs($toRequire);
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
  protected function _requireInlineJs($javascript)
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
      return $this->_requireInlineCss($toRequire);
    }
    Dispatch::instance()->store()->requireCss($this->getResourceUri($toRequire), $options);
    return $this;
  }

  /**
   * Add css to the store, ignoring exceptions
   *
   * @param string $toRequire filename, or CSS if inline manager
   * @param        $options
   *
   * @return ResourceManager
   */
  public function includeCss($toRequire, $options = null)
  {
    try
    {
      return $this->requireCss($toRequire, $options);
    }
    catch(Exception $e)
    {
      return $this;
    }
  }

  /**
   * Add css to the store
   *
   * @param $stylesheet
   *
   * @return ResourceManager
   */
  protected function _requireInlineCss($stylesheet)
  {
    Dispatch::instance()->store()->requireInlineCss($stylesheet);
    return $this;
  }
}
