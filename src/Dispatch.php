<?php
namespace Packaged\Dispatch;

use Packaged\Helpers\Path;

class Dispatch
{
  /**
   * @var Dispatch
   */
  private static $_instance;

  const RESOURCES_DIR = 'resources';

  public static function bind(Dispatch $instance)
  {
    self::$_instance = $instance;
    return $instance;
  }

  public static function instance()
  {
    return self::$_instance;
  }

  protected $_aliases = [];
  protected $_projectRoot;

  public function __construct($projectRoot)
  {
    $this->_projectRoot = $projectRoot;
  }

  public function getResourcesPath()
  {
    return Path::system($this->_projectRoot, self::RESOURCES_DIR);
  }

  public function getVendorPath($vendor, $package)
  {
    return Path::system($this->_projectRoot, 'vendor', $vendor, $package);
  }

  public function addAlias($alias, $path)
  {
    $this->_aliases[$alias] = $path;
    return $this;
  }

  public function getAliasPath($alias)
  {
    return $this->_aliases[$alias] ?? null;
  }

}
