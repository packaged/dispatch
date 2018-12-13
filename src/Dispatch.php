<?php
namespace Packaged\Dispatch;

use Packaged\Dispatch\Manager\ResourceManager;
use Packaged\Dispatch\Resources\AbstractResource;
use Packaged\Dispatch\Resources\DispatchableResource;
use Packaged\Dispatch\Resources\ResourceFactory;
use Packaged\Helpers\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Dispatch
{
  /**
   * @var Dispatch
   */
  private static $_instance;

  const RESOURCES_DIR = 'resources';
  const VENDOR_DIR = 'vendor';
  const PUBLIC_DIR = 'public';

  public static function bind(Dispatch $instance)
  {
    self::$_instance = $instance;
    return $instance;
  }

  public static function instance()
  {
    return self::$_instance;
  }

  public static function destroy()
  {
    self::$_instance = null;
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

  public function getPublicPath()
  {
    return Path::system($this->_projectRoot, self::PUBLIC_DIR);
  }

  public function getVendorPath($vendor, $package)
  {
    return Path::system($this->_projectRoot, self::VENDOR_DIR, $vendor, $package);
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

  public function handle(Request $request): Response
  {
    $pathParts = array_filter(explode('/', $request->getPathInfo()));
    $type = array_shift($pathParts);
    switch($type)
    {
      case ResourceManager::MAP_RESOURCES:
        $manager = ResourceManager::resources();
        break;
      case ResourceManager::MAP_ALIAS:
        $manager = ResourceManager::alias(array_shift($pathParts));
        break;
      case ResourceManager::MAP_VENDOR:
        $manager = ResourceManager::vendor(array_shift($pathParts), array_shift($pathParts));
        break;
      case ResourceManager::MAP_PUBLIC:
      default:
        $manager = ResourceManager::public();
        break;
    }

    //Remove the hash from the URL
    array_shift($pathParts);

    $fullPath = $manager->getFilePath(Path::custom('/', $pathParts));

    $resource = ResourceFactory::getExtensionResource('css');
    if($resource instanceof DispatchableResource)
    {
      $resource->setManager($manager);
    }
    if($resource instanceof AbstractResource)
    {
      $resource->setContent(file_get_contents($fullPath));
    }
    return ResourceFactory::create($resource);

    return Response::create($fullPath);
  }

}
