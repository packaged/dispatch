<?php
namespace Packaged\Dispatch;

use Exception;
use Packaged\Dispatch\Component\DispatchableComponent;
use Packaged\Dispatch\Component\FixedClassComponent;
use Packaged\Helpers\BitWise;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;
use Packaged\Helpers\ValueAs;
use ReflectionClass;
use RuntimeException;
use function apcu_fetch;
use function apcu_store;
use function array_unshift;
use function base_convert;
use function count;
use function explode;
use function file_exists;
use function filectime;
use function function_exists;
use function get_class;
use function in_array;
use function ltrim;
use function md5;
use function md5_file;
use function str_replace;
use function strlen;
use function substr;

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
  const OPT_RESOURCE_STORE = 'resource.store';

  protected $_type = self::MAP_RESOURCES;
  protected $_mapOptions = [];
  protected $_uriPrefix;
  protected $_baseUri;
  protected $_componentPath;
  protected $_options = [];

  protected static $defaultOptions = [
    self::OPT_THROW_ON_FILE_NOT_FOUND => true,
    self::OPT_RESOURCE_STORE          => null,
  ];

  /**
   * @var Dispatch|null Dispatch in use for components to calculate paths
   */
  private $_dispatch;
  /**
   * @var DispatchableComponent
   */
  private $_component;
  /**
   * @var ResourceStore
   */
  protected $_store;

  public function __construct($type, array $mapOptions = [], array $options = [], ?Dispatch $dispatch = null)
  {
    $this->_type = $type;
    $this->_mapOptions = $mapOptions;
    foreach($options as $option => $optionValue)
    {
      $this->setOption($option, $optionValue);
    }
    $this->_options = $options;
    if($dispatch !== null)
    {
      $this->setDispatch($dispatch);
    }
  }

  protected function _dispatch(): ?Dispatch
  {
    return $this->_dispatch ?? Dispatch::instance();
  }

  public function setDispatch(Dispatch $dispatch)
  {
    $this->_dispatch = $dispatch;
    if($this->_store === null)
    {
      $this->_store = $dispatch->store();
    }
    return $this;
  }

  public function getBaseUri()
  {
    if($this->_baseUri === null)
    {
      $d = $this->_dispatch();
      $this->_baseUri = $d ? $d->getBaseUri() : '';
      $this->_baseUri = Path::url($this->_baseUri, $this->_type, $this->_uriPrefix ?? implode('/', $this->_mapOptions));
    }
    return $this->_baseUri;
  }

  /**
   * @return ResourceStore
   */
  public function getResourceStore(): ResourceStore
  {
    return $this->_store ?: $this->_dispatch()->store();
  }

  public function hasResourceStore(): bool
  {
    return $this->_store !== null;
  }

  /**
   * Remove any custom resource store set, and write to the global store
   *
   * @return $this
   */
  public function useGlobalResourceStore()
  {
    $this->_store = null;
    return $this;
  }

  /**
   * @param ResourceStore $store
   *
   * @return $this
   */
  public function setResourceStore(ResourceStore $store)
  {
    $this->_store = $store;
    return $this;
  }

  public static function vendor($vendor, $package, $options = [], ?Dispatch $dispatch = null)
  {
    $rm = new static(self::MAP_VENDOR, [$vendor, $package], $options, $dispatch);
    $dispatch = $rm->_dispatch();
    $rm->_uriPrefix = $dispatch ? $dispatch->getVendorOptions($vendor, $package) : null;
    return $rm;
  }

  public static function alias($alias, $options = [], ?Dispatch $dispatch = null)
  {
    return new static(self::MAP_ALIAS, [$alias], $options, $dispatch);
  }

  public static function resources($options = [], ?Dispatch $dispatch = null)
  {
    return new static(self::MAP_RESOURCES, [], $options, $dispatch);
  }

  public static function public($options = [], ?Dispatch $dispatch = null)
  {
    return new static(self::MAP_PUBLIC, [], $options, $dispatch);
  }

  public static function inline($options = [], ?Dispatch $dispatch = null)
  {
    return new static(self::MAP_INLINE, [], $options, $dispatch);
  }

  public static function external($options = [], ?Dispatch $dispatch = null)
  {
    return new static(self::MAP_EXTERNAL, [], $options, $dispatch);
  }

  public static function component(DispatchableComponent $component, $options = [], ?Dispatch $dispatch = null)
  {
    $fullClass = $component instanceof FixedClassComponent ? $component->getComponentClass() : get_class($component);
    $manager = static::_componentManager($fullClass, $dispatch, $options);
    $manager->_component = $component;
    return $manager;
  }

  //Component Manager Caching
  protected static $cmc = [];

  protected static function _componentManager($fullClass, Dispatch $dispatch = null, $options = []): ResourceManager
  {
    if(isset(static::$cmc[$fullClass]))
    {
      return static::$cmc[$fullClass];
    }

    $class = ltrim($fullClass, '\\');
    if(!$dispatch)
    {
      $dispatch = Dispatch::instance();
      if($dispatch === null)
      {
        throw new RuntimeException("Dispatch must be available to use the component manager");
      }
    }

    $maxPrefix = $maxAlias = '';
    $prefixLen = 0;
    foreach($dispatch->getComponentAliases() as $alias => $namespace)
    {
      $trimNs = ltrim($namespace, '\\');
      $len = strlen($trimNs);
      if(Strings::startsWith($class, $trimNs, true, $len) && $len > $prefixLen)
      {
        $maxPrefix = $trimNs;
        $prefixLen = $len;
        $maxAlias = $alias;
      }
    }
    $class = str_replace($maxPrefix, $maxAlias, $class);
    $parts = explode('\\', $class);
    array_unshift($parts, (string)count($parts));

    $manager = new static(self::MAP_COMPONENT, $parts, $options);
    $manager->_componentPath = $dispatch->componentClassResourcePath($fullClass);
    $manager->_dispatch = $dispatch;
    static::$cmc[$fullClass] = $manager;
    return $manager;
  }

  public function setOption($option, $value)
  {
    if($option === self::OPT_RESOURCE_STORE && $value instanceof ResourceStore)
    {
      $this->setResourceStore($value);
    }
    $this->_options[$option] = $value;
    return $this;
  }

  public function getMapType()
  {
    return $this->_type;
  }

  public function getMapOptions()
  {
    return $this->_mapOptions;
  }

  /**
   * Add js to the store, ignoring exceptions
   *
   * @param string $toRequire filename, or JS if inline manager
   * @param        $options
   *
   * @param int    $priority
   *
   * @return ResourceManager
   */
  public function includeJs($toRequire, ?array $options = [], int $priority = ResourceStore::PRIORITY_DEFAULT)
  {
    try
    {
      return $this->requireJs($toRequire, $options, $priority);
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
   * @param int    $priority
   *
   * @return ResourceManager
   * @throws Exception
   */
  public function requireJs($toRequire, ?array $options = [], int $priority = ResourceStore::PRIORITY_DEFAULT)
  {
    if($this->_type == self::MAP_INLINE)
    {
      return $this->_requireInlineJs($toRequire, $options, $priority);
    }
    $this->getResourceStore()->requireJs($this->getResourceUri($toRequire, false), $options, $priority);
    return $this;
  }

  /**
   * Add a js script to the store
   *
   * @param            $javascript
   *
   * @param array|null $options
   * @param int        $priority
   *
   * @return ResourceManager
   */
  protected function _requireInlineJs($javascript, ?array $options = [], int $priority = ResourceStore::PRIORITY_DEFAULT
  )
  {
    $this->getResourceStore()->requireInlineJs($javascript, $options, $priority);
    return $this;
  }

  protected $_resourceUriCache = [];

  /**
   * @param      $relativeFullPath
   *
   * @param bool $allowComponentBubble If the resource does not exist in a component, attempt to load from its parent
   *
   * @param null $flags
   *
   * @return string|null
   * @throws \ReflectionException
   */
  public function getResourceUri($relativeFullPath, bool $allowComponentBubble = true, $flags = null): ?string
  {
    if($this->_type == self::MAP_EXTERNAL || $this->isExternalUrl($relativeFullPath))
    {
      return $relativeFullPath;
    }

    $cacheKey = ($allowComponentBubble ? '1' : '0') . $relativeFullPath . $flags;
    if(!isset($this->_resourceUriCache[$cacheKey]))
    {

      [$filePath, $relativeFullPath] = $this->_optimisePath($this->getFilePath($relativeFullPath), $relativeFullPath);
      //Do not allow bubbling if the component is a fixed class component
      if($allowComponentBubble && $this->_component && $this->_component instanceof FixedClassComponent)
      {
        $allowComponentBubble = false;
      }
      if($allowComponentBubble && $this->_type == self::MAP_COMPONENT && $this->_component && !file_exists($filePath))
      {
        $parent = (new ReflectionClass($this->_component))->getParentClass();
        if($parent && !$parent->isAbstract() && $parent->implementsInterface(DispatchableComponent::class))
        {
          return self::componentClass($parent->getName(), $this->_options)
            ->getResourceUri($relativeFullPath, $allowComponentBubble);
        }
      }
      $relHash = $this->getRelativeHash($filePath);
      $hash = $this->getFileHash($filePath);

      $bits = $this->_dispatch()->getBits();
      if($flags !== null)
      {
        $bits = BitWise::add($bits, $flags);
      }

      if(!$hash)
      {
        return null;
      }

      $uri = $this->getBaseUri();
      $this->_resourceUriCache[$cacheKey] = $uri . (empty($uri) ? '' : '/') . $hash . $relHash
        . ($bits > 0 ? '-' . base_convert($bits, 10, 36) : '') . '/' . $relativeFullPath;
    }

    return $this->_resourceUriCache[$cacheKey];
  }

  protected $_optimizeWebP;

  protected function _optimisePath($path, $relativeFullPath)
  {
    if($this->_optimizeWebP === null)
    {
      $this->_optimizeWebP = ValueAs::bool($this->_dispatch()->config()->getItem('optimisation', 'webp', false));
    }

    if($this->_optimizeWebP && BitWise::has($this->_dispatch()->getBits(), Dispatch::FLAG_WEBP)
      && in_array(substr($path, -4), ['.jpg', 'jpeg', '.png', '.gif', '.bmp', 'tiff', '.svg'])
      && file_exists($path . '.webp'))
    {
      return [$path . '.webp', $relativeFullPath . '.webp'];
    }
    return [$path, $relativeFullPath];
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
    return isset($path[8])
      && (
        ($path[0] == '/' && $path[1] == '/')
        || strncasecmp($path, 'http://', 7) == 0
        || strncasecmp($path, 'https://', 8) == 0);
  }

  /**
   * @param      $relativePath
   *
   * @return string
   * @throws Exception
   */
  public function getFilePath($relativePath)
  {
    if($this->_type == self::MAP_RESOURCES)
    {
      return Path::system($this->_dispatch()->getResourcesPath(), $relativePath);
    }
    else if($this->_type == self::MAP_PUBLIC)
    {
      return Path::system($this->_dispatch()->getPublicPath(), $relativePath);
    }
    else if($this->_type == self::MAP_VENDOR)
    {
      [$vendor, $package] = $this->_mapOptions;
      return Path::system($this->_dispatch()->getVendorPath($vendor, $package), $relativePath);
    }
    else if($this->_type == self::MAP_ALIAS)
    {
      return Path::system($this->_dispatch()->getAliasPath($this->_mapOptions[0]), $relativePath);
    }
    else if($this->_type == self::MAP_COMPONENT)
    {
      return Path::system($this->_componentPath, $relativePath);
    }
    throw new Exception("invalid map type");
  }

  public static function componentClass(string $componentClassName, $options = [], ?Dispatch $dispatch = null)
  {
    return static::_componentManager($componentClassName, $dispatch, $options);
  }

  public function getRelativeHash($filePath)
  {
    return $this->_dispatch()->generateHash($this->_dispatch()->calculateRelativePath($filePath), 4);
  }

  protected static $_fileHashCache = [];

  public function getFileHash($fullPath)
  {
    $cached = static::$_fileHashCache[$fullPath] ?? null;

    if($cached === -1 || ($cached === null && !file_exists($fullPath)))
    {
      self::$_fileHashCache[$fullPath] = -1;
      if($this->getOption(self::OPT_THROW_ON_FILE_NOT_FOUND, true))
      {
        throw new RuntimeException("Unable to find dispatch file '$fullPath'", 404);
      }
      return null;
    }

    if(!empty($cached))
    {
      return $cached;
    }

    $key = 'pdspfh-' . md5($fullPath) . '-' . filectime($fullPath);

    if(function_exists("apcu_fetch"))
    {
      $exists = null;
      $hash = apcu_fetch($key, $exists);
      if($exists && $hash)
      {
        // @codeCoverageIgnoreStart
        self::$_fileHashCache[$fullPath] = $hash;
        return $hash;
        // @codeCoverageIgnoreEnd
      }
    }

    self::$_fileHashCache[$fullPath] = $hash = $this->_dispatch()->generateHash(md5_file($fullPath), 8);
    if($hash && function_exists('apcu_store'))
    {
      apcu_store($key, $hash, 86400);
    }

    return $hash;
  }

  public function getOption($option, $default = null)
  {
    return $this->_options[$option] ?? $this->_defaultOption($option, $default);
  }

  /**
   * Add css to the store, ignoring exceptions
   *
   * @param string $toRequire filename, or CSS if inline manager
   * @param        $options
   *
   * @param int    $priority
   *
   * @return ResourceManager
   */
  public function includeCss($toRequire, ?array $options = [], int $priority = ResourceStore::PRIORITY_DEFAULT)
  {
    try
    {
      return $this->requireCss($toRequire, $options, $priority);
    }
    catch(Exception $e)
    {
      return $this;
    }
  }

  /**
   * Add css to the store
   *
   * @param string $toRequire filename, or CSS if inline manager
   * @param        $options
   *
   * @param int    $priority
   *
   * @return ResourceManager
   * @throws Exception
   */
  public function requireCss($toRequire, ?array $options = [], int $priority = ResourceStore::PRIORITY_DEFAULT)
  {
    if($this->_type == self::MAP_INLINE)
    {
      return $this->_requireInlineCss($toRequire, $options, $priority);
    }
    $this->getResourceStore()->requireCss($this->getResourceUri($toRequire, false), $options, $priority);
    return $this;
  }

  /**
   * Add css to the store
   *
   * @param            $stylesheet
   *
   * @param array|null $options
   * @param int        $priority
   *
   * @return ResourceManager
   */
  protected function _requireInlineCss(
    $stylesheet, ?array $options = [], int $priority = ResourceStore::PRIORITY_DEFAULT
  )
  {
    $this->getResourceStore()->requireInlineCss($stylesheet, $options, $priority);
    return $this;
  }

  public static function clearCache()
  {
    static::$cmc = [];
    static::$_fileHashCache = [];
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return void
   */
  public static function setDefaultOption(string $key, $value)
  {
    static::$defaultOptions[$key] = $value;
  }

  protected function _defaultOption(string $key, $default = null)
  {
    return static::$defaultOptions[$key] ?? $default;
  }
}
