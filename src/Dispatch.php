<?php
namespace Packaged\Dispatch;

use Packaged\Dispatch\Component\DispatchableComponent;
use Packaged\Dispatch\Resources\AbstractDispatchableResource;
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

  /**
   * @var ResourceStore
   */
  protected $_resourceStore;

  protected $_baseUri;

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
  protected $_componentAliases = [];

  public function __construct($projectRoot, $baseUri = null)
  {
    $this->_projectRoot = $projectRoot;
    $this->_resourceStore = new ResourceStore();
    $this->_baseUri = $baseUri;
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
    return isset($this->_aliases[$alias]) ? Path::system($this->_projectRoot, $this->_aliases[$alias]) : null;
  }

  public function getBaseUri()
  {
    return $this->_baseUri;
  }

  public function addComponentAlias($namespace, $alias)
  {
    $this->_componentAliases['_' . $alias] = $namespace;
    return $this;
  }

  public function getComponentAliases()
  {
    return $this->_componentAliases;
  }

  /**
   * @param Request $request
   *
   * @return Response
   * @throws \Exception
   */
  public function handle(Request $request): Response
  {
    $path = substr($request->getPathInfo(), strlen(Request::create($this->_baseUri)->getPathInfo()));
    $pathParts = array_filter(explode('/', $path));
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
        $manager = ResourceManager::public();
        break;
      case ResourceManager::MAP_COMPONENT:
        $len = array_shift($pathParts);
        $class = '';
        for($i = 0; $i < $len; $i++)
        {
          $part = array_shift($pathParts);
          if($i == 0 && isset($this->_componentAliases[$part]))
          {
            $class = $this->_componentAliases[$part];
          }
          else
          {
            $class .= '\\' . $part;
          }
        }
        if(class_exists($class))
        {
          $component = new $class();
          if($component instanceof DispatchableComponent)
          {
            $manager = ResourceManager::component($component);
          }
        }
        if(!isset($manager))
        {
          return Response::create("Component Not Found", 404);
        }
        break;
      default:
        return Response::create("File Not Found", 404);
    }

    //Remove the hash from the URL
    $compareHash = array_shift($pathParts);

    $requestPath = Path::custom('/', $pathParts);
    $fullPath = $manager->getFilePath($requestPath);
    if($compareHash !== $manager->getFileHash($fullPath))
    {
      return Response::create("File Not Found", 404);
    }

    $resource = ResourceFactory::getExtensionResource(pathinfo($fullPath, PATHINFO_EXTENSION));
    if($resource instanceof DispatchableResource)
    {
      $resource->setManager($manager);
    }
    if($resource instanceof AbstractDispatchableResource)
    {
      $resource->setProcessingPath($requestPath);
    }
    if($resource instanceof AbstractResource)
    {
      $resource->setFilePath($fullPath);
      $resource->setContent(file_get_contents($fullPath));
    }
    return ResourceFactory::create($resource);
  }

  public function store()
  {
    return $this->_resourceStore;
  }

}
