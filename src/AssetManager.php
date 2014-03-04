<?php
namespace Packaged\Dispatch;

use Packaged\Helpers\Strings;
use Packaged\Helpers\ValueAs;

/**
 * Helper class for creating resource uris, and storing required css/js files
 */
class AssetManager
{
  /**
   * Storage container for the resources requested from all asset managers
   */
  protected static $_resourceStore;

  /**
   * Resource location type
   *
   * @var string
   */
  protected $_mapType = DirectoryMapper::MAP_SOURCE;
  /**
   * Parts to build the uri with, e.g. vendor,package
   * @var array
   */
  protected $_lookupParts = [];

  /**
   * Retrieve all requested resources by type
   *
   * @param string $type
   *
   * @return mixed
   */
  public static function getUrisByType($type = 'js')
  {
    return static::$_resourceStore[$type];
  }

  /**
   * Asset manager for an aliased directory
   *
   * @param $alias
   *
   * @return static
   */
  public static function aliasType($alias)
  {
    return new static(null, DirectoryMapper::MAP_ALIAS, [$alias]);
  }

  /**
   * Asset manager for the assets directory
   *
   * @return static
   */
  public static function assetType()
  {
    return new static(null, DirectoryMapper::MAP_ASSET);
  }

  /**
   * Asset manager for a vendor package
   *
   * @param $vendor
   * @param $package
   *
   * @return static
   */
  public static function vendorType($vendor, $package)
  {
    return new static(null, DirectoryMapper::MAP_VENDOR, [$vendor, $package]);
  }

  /**
   * Create a new asset manager based on the calling class
   *
   * @param object $callee
   * @param string $forceType DirectoryMapper::MAP_*
   * @param mixed  $lookupParts
   */
  public function __construct(
    $callee = null, $forceType = null, $lookupParts = null
  )
  {
    $this->_mapType = $forceType;

    if($forceType === null)
    {
      $this->_mapType = $this->mapType($callee);
    }

    //If provided, use the lookup parts
    if($lookupParts !== null)
    {
      $this->_lookupParts = ValueAs::arr($lookupParts);
    }
  }

  /**
   * Find the map type based on the provided object
   *
   * @param $object
   *
   * @return string
   */
  public function mapType($object)
  {
    $reflection = new \ReflectionObject($object);
    $filename   = $reflection->getFileName();

    //Find the common start to the filename of the callee and this file, which
    //is known to be in the vendor directory
    $prefix = Strings::commonPrefix(
      $filename,
      '/Websites/cubex/skeleton/vendor/packaged/dispatch/asset.php'
    );

    //Account for other packaged repos that may offer resources
    if(ends_with($prefix, 'packaged/'))
    {
      $prefix = substr($prefix, 0, -9);
    }

    //Calculate the vendor and package names
    if(ends_with($prefix, '/vendor/'))
    {
      $path               = substr($filename, strlen($prefix));
      $this->_lookupParts = array_slice(explode('/', $path, 3), 0, 2);
      return DirectoryMapper::MAP_VENDOR;
    }
    return DirectoryMapper::MAP_SOURCE;
  }

  /**
   * Generate a resource uri
   *
   * @param $filename
   * @param $path
   *
   * @return mixed
   */
  public function getResourceUri($filename, $path = null)
  {
    $event = new DispatchEvent();
    $event->setFilename($filename);
    $event->setLookupParts((array)$this->_lookupParts);
    $event->setMapType($this->_mapType);
    $event->setPath($path);
    $result = Dispatch::trigger($event);
    if($result !== null)
    {
      return $result->getResult();
    }
    return null;
  }

  /**
   * Add a resource to the store, along with its type
   *
   * @param $type
   * @param $uri
   * @param $options
   */
  protected function _addToStore($type, $uri, $options = null)
  {
    if(!isset(static::$_resourceStore[$type]))
    {
      static::$_resourceStore[$type] = [];
    }

    static::$_resourceStore[$type][$uri] = $options;
  }

  /**
   * Add a js file to the store
   *
   * @param $filename
   * @param $options
   */
  public function requireJs($filename, $options = null)
  {
    static::_addToStore(
      'js',
      $this->getResourceUri($filename . '.js'),
      $options
    );
  }

  /**
   * Add a css file to the store
   *
   * @param $filename
   * @param $options
   */
  public function requireCss($filename, $options = null)
  {
    static::_addToStore(
      'css',
      $this->getResourceUri($filename . '.css'),
      $options
    );
  }
}
