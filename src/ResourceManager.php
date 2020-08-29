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
  protected $_baseUri;
  protected $_componentPath;
  protected $_options = [];

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

  public function __construct($type, array $mapOptions = [], array $options = [])
  {
    $this->_type = $type;
    $this->_mapOptions = $mapOptions;
    foreach($options as $option => $optionValue)
    {
      $this->setOption($option, $optionValue);
    }
    $this->_options = $options;
  }

  public function getBaseUri()
  {
    if($this->_baseUri === null)
    {
      $this->_baseUri = Dispatch::instance() ? Dispatch::instance()->getBaseUri() : '';
      $this->_baseUri = Path::url($this->_baseUri, $this->_type, implode('/', $this->_mapOptions));
    }
    return $this->_baseUri;
  }

  /**
   * @return ResourceStore
   */
  public function getResourceStore(): ResourceStore
  {
    return $this->_store ?: Dispatch::instance()->store();
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
    $manager = static::_componentManager($fullClass, Dispatch::instance(), $options);
    $manager->_component = $component;
    return $manager;
  }

  protected static function _componentManager($fullClass, Dispatch $dispatch = null, $options = []): ResourceManager
  {
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

    $bits = Dispatch::instance()->getBits();
    if($flags !== null)
    {
      $bits = BitWise::add($bits, $flags);
    }

    if(!$hash)
    {
      return null;
    }

    $uri = $this->getBaseUri();
    return $uri . (empty($uri) ? '' : '/') . $hash . $relHash . ($bits > 0 ? '-' . base_convert($bits, 10, 36) : '')
      . '/' . $relativeFullPath;
  }

  protected $_optimizeWebP;

  protected function _optimisePath($path, $relativeFullPath)
  {
    if($this->_optimizeWebP === null)
    {
      $this->_optimizeWebP = ValueAs::bool(Dispatch::instance()->config()->getItem('optimisation', 'webp', false));
    }

    if($this->_optimizeWebP && BitWise::has(($this->_dispatch ?: Dispatch::instance())->getBits(), Dispatch::FLAG_WEBP)
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
    throw new Exception("invalid map type");
  }

  public static function componentClass(string $componentClassName, $options = [])
  {
    return static::_componentManager($componentClassName, Dispatch::instance(), $options);
  }

  public function getRelativeHash($filePath)
  {
    return Dispatch::instance()->generateHash(Dispatch::instance()->calculateRelativePath($filePath), 4);
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

    self::$_fileHashCache[$fullPath] = $hash = Dispatch::instance()->generateHash(md5_file($fullPath), 8);
    if($hash && function_exists('apcu_store'))
    {
      apcu_store($key, $hash, 86400);
    }

    return $hash;
  }

  public function getOption($option, $default = null)
  {
    return $this->_options[$option] ?? $default;
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
}
