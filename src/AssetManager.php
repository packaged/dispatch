<?php
namespace Packaged\Dispatch;

use Packaged\Helpers\Strings;
use Packaged\Helpers\ValueAs;

/**
 * Helper class for creating resource uris, and storing required css/js files
 */
class AssetManager
{
  const TYPE_CSS = 'css';
  const TYPE_JS = 'js';
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
   * Relative path on the current asset (when handling sub asset construction)
   *
   * @var array folders in the path
   */
  protected $_path;

  /**
   * Retrieve all requested resources by type
   *
   * @param string $type
   *
   * @return array|null
   */
  public static function getUrisByType($type = self::TYPE_JS)
  {
    return isset(static::$_resourceStore[$type]) ?
      static::$_resourceStore[$type] : null;
  }

  public static function generateHtmlIncludes($for = self::TYPE_CSS)
  {
    if(!isset(static::$_resourceStore[$for])
      || empty(static::$_resourceStore[$for])
    )
    {
      return '';
    }

    $template = '<link href="%s"%s>';

    if($for == self::TYPE_CSS)
    {
      $template = '<link href="%s" rel="stylesheet" type="text/css"%s>';
    }
    else if($for == self::TYPE_JS)
    {
      $template = '<script src="%s"%s></script>';
    }

    $return = '';
    foreach(static::$_resourceStore[$for] as $uri => $options)
    {
      if(strlen($uri) == 32 && !stristr($uri, '/'))
      {
        if($for == self::TYPE_CSS)
        {
          $return .= '<style>' . $options . '</style>';
        }
        else if($for == self::TYPE_JS)
        {
          $return .= '<script>' . $options . '</script>';
        }
      }
      else if(!empty($uri))
      {
        $opts = $options;
        if(is_array($options))
        {
          $opts = '';
          foreach($options as $key => $value)
          {
            $value = ValueAs::string($value);
            $opts .= " $key=\"$value\"";
          }
        }
        $return .= sprintf($template, $uri, $opts);
      }
    }
    return $return;
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
   * Asset manager for the source directory
   *
   * @return static
   */
  public static function sourceType()
  {
    return new static(null, DirectoryMapper::MAP_SOURCE);
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
   * Create an asset manager based on an existing dispatched uri
   *
   * @param $uri
   *
   * @return null|AssetManager
   */
  public static function buildFromUri($uri)
  {
    $parts = explode('/', $uri);
    switch($parts[0])
    {
      case DirectoryMapper::MAP_ALIAS:
        return static::aliasType($parts[1])
          ->_setRelativePath(array_slice($parts, 5, -1));
      case DirectoryMapper::MAP_SOURCE:
        return static::sourceType()
          ->_setRelativePath(array_slice($parts, 4, -1));
      case DirectoryMapper::MAP_VENDOR:
        return static::vendorType($parts[1], $parts[2])
          ->_setRelativePath(array_slice($parts, 6, -1));
      case DirectoryMapper::MAP_ASSET:
        return static::assetType()
          ->_setRelativePath(array_slice($parts, 4, -1));
    }
    return null;
  }

  /**
   * Set the path of the dispatching asset, when building from uri
   *
   * @param $path
   *
   * @return $this
   */
  protected function _setRelativePath($path)
  {
    $this->_path = $path;
    return $this;
  }

  /**
   * Return the relative path of the parent dispatching asset
   *
   * @return array
   */
  public function getRelativePath()
  {
    return $this->_path;
  }

  /**
   * Create a new asset manager based on the calling class
   *
   * @param object $callee
   * @param string $forceType DirectoryMapper::MAP_*
   * @param mixed  $lookupParts
   *
   * @throws \Exception
   */
  public function __construct(
    $callee = null, $forceType = null, $lookupParts = null
  )
  {
    $this->_mapType = $forceType;

    if($forceType === null)
    {
      if(!is_object($callee))
      {
        throw new \Exception(
          "You cannot construct an asset manager without specifying " .
          "either a callee or forceType"
        );
      }

      $this->_mapType = $this->lookupMapType($callee);
    }

    //If provided, use the lookup parts
    if($lookupParts !== null)
    {
      $this->_lookupParts = ValueAs::arr($lookupParts);
    }
  }

  /**
   * Retrieve the map type currently set
   *
   * @return string
   */
  public function getMapType()
  {
    return $this->_mapType;
  }

  protected function ownFile()
  {
    return __FILE__;
  }

  /**
   * Find the map type based on the provided object
   *
   * @param $object
   *
   * @return string
   */
  public function lookupMapType($object)
  {
    $reflection = new \ReflectionObject($object);
    $filename = $reflection->getFileName();

    //Find the common start to the filename of the callee and this file, which
    //is known to be in the vendor directory
    $prefix = Strings::commonPrefix($filename, $this->ownFile());

    //Account for other packaged repos that may offer resources
    if(Strings::endsWith($prefix, 'packaged' . DIRECTORY_SEPARATOR))
    {
      $prefix = substr($prefix, 0, -9);
    }

    //Calculate the vendor and package names
    if(Strings::endsWith($prefix, 'vendor' . DIRECTORY_SEPARATOR))
    {
      $path = substr($filename, strlen($prefix));
      $this->_lookupParts = array_slice(explode('/', $path, 3), 0, 2);
      return DirectoryMapper::MAP_VENDOR;
    }
    return DirectoryMapper::MAP_SOURCE;
  }

  /**
   * Return the configured lookup parts e.g. Vendor,Package
   * @return array
   */
  public function getLookupParts()
  {
    return $this->_lookupParts;
  }

  /**
   * Generate a resource uri
   *
   * @param $filename
   * @param $path
   * @param $extension
   *
   * @return mixed
   */
  public function getResourceUri($filename, $path = null, $extension = null)
  {
    //If no filename is sent, the resource is very unlikely to be valid
    if(empty($filename))
    {
      return null;
    }

    if($this->isExternalUrl($filename))
    {
      return $filename;
    }

    if($extension !== null)
    {
      if(substr($filename, -4) !== '.min')
      {
        $filename = [
          $filename . '.min.' . $extension,
          $filename . '.' . $extension,
        ];
      }
      else
      {
        $filename = [$filename . '.' . $extension];
      }
    }
    $event = new DispatchEvent();
    $event->setLookupParts((array)$this->_lookupParts);
    $event->setMapType($this->_mapType);
    $event->setPath($path);

    foreach((array)$filename as $fname)
    {
      $event->setFilename($fname);
      $result = Dispatch::trigger($event);
      if($result !== null && $result->getResult() !== null)
      {
        return $result->getResult();
      }
    }
    return null;
  }

  /**
   * Detect if URL has a protocol
   *
   * @param string $path
   *
   * @return bool
   */
  private function isExternalUrl($path)
  {
    return (strlen($path) > 8) && Strings::startsWithAny(
      $path,
      ['http://', 'https://', '//']
    );
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
    if(!empty($uri))
    {
      if(!isset(static::$_resourceStore[$type]))
      {
        static::$_resourceStore[$type] = [];
      }

      static::$_resourceStore[$type][$uri] = $options;
    }
  }

  /**
   * Clear the entire resource store with a type of null, or all items stored
   * by a type if supplied
   *
   * @param null $type
   */
  public function clearStore($type = null)
  {
    if($type === null)
    {
      static::$_resourceStore = [];
    }
    else
    {
      unset(static::$_resourceStore[$type]);
    }
  }

  /**
   * Add a js file to the store
   *
   * @param $filename
   * @param $options
   */
  public function requireJs($filename, $options = null)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      static::_addToStore(
        'js',
        $this->getResourceUri($filename, null, 'js'),
        $options
      );
    }
  }

  /**
   * Add a js script to the store
   *
   * @param $javascript
   */
  public function requireInlineJs($javascript)
  {
    static::_addToStore('js', md5($javascript), $javascript);
  }

  /**
   * Add a css file to the store
   *
   * @param $filename
   * @param $options
   */
  public function requireCss($filename, $options = null)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      static::_addToStore(
        'css',
        $this->getResourceUri($filename, null, 'css'),
        $options
      );
    }
  }

  /**
   * Add css to the store
   *
   * @param $stylesheet
   */
  public function requireInlineCss($stylesheet)
  {
    static::_addToStore('css', md5($stylesheet), $stylesheet);
  }
}
