<?php
namespace Packaged\Dispatch;

use Composer\Autoload\ClassLoader;
use Packaged\Config\Provider\ConfigProvider;
use Packaged\Dispatch\Resources\AbstractDispatchableResource;
use Packaged\Dispatch\Resources\AbstractResource;
use Packaged\Dispatch\Resources\DispatchableResource;
use Packaged\Dispatch\Resources\ResourceFactory;
use Packaged\Helpers\Path;
use RuntimeException;
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
  protected $_requireFileHash = false;
  /**
   * @var ConfigProvider
   */
  protected $_config;

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
  /**
   * @var ClassLoader
   */
  protected $_classLoader;
  protected $_hashSalt = 'dispatch';

  public function __construct($projectRoot, $baseUri = null, ClassLoader $loader = null)
  {
    $this->_projectRoot = $projectRoot;
    $this->_config = new ConfigProvider();
    $this->_resourceStore = new ResourceStore();
    $this->_baseUri = $baseUri;
    $this->_classLoader = $loader;
  }

  /**
   * Add salt to dispatch hashes, for additional resource security
   *
   * @param string $hashSalt
   *
   * @return $this
   */
  public function setHashSalt(string $hashSalt)
  {
    $this->_hashSalt = $hashSalt;
    return $this;
  }

  /**
   * Generate a hash against specific content, for a desired length
   *
   * @param      $content
   * @param int  $length
   *
   * @return string
   */
  public function generateHash($content, int $length = null)
  {
    $hash = md5($content . $this->_hashSalt);
    if($length !== null)
    {
      return substr($hash, 0, $length);
    }
    return $hash;
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
  public function handleRequest(Request $request): Response
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

        if(!empty($class))
        {
          try
          {
            $manager = ResourceManager::componentClass($class);
          }
          catch(RuntimeException $e)
          {
            //Class Loader not available
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

    [$fileHash, $relativeHash] = str_split($compareHash . ' ', 8);
    $relativeHash = trim($relativeHash);
    $failedHash = true;
    if(!$this->_requireFileHash && $relativeHash && $relativeHash === $manager->getRelativeHash($fullPath))
    {
      $failedHash = false;
    }

    if((!$relativeHash || $failedHash) && $fileHash === $manager->getFileHash($fullPath))
    {
      $failedHash = false;
    }

    if($failedHash)
    {
      return Response::create("File Not Found", 404);
    }

    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
    $resource = ResourceFactory::getExtensionResource($ext);
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

      if($this->config()->has('ext.' . $ext))
      {
        $resource->setOptions($this->config()->getSection('ext.' . $ext)->getItems());
      }
    }
    return ResourceFactory::create($resource);
  }

  public function componentClassResourcePath($class)
  {
    $loader = $this->_getClassLoader();
    if($loader instanceof ClassLoader)
    {
      $file = $loader->findFile(ltrim($class, '\\'));
      if(!$file)
      {
        throw new RuntimeException("Unable to load class");
      }
      return Path::system(dirname(realpath($file)), '_resources');
    }
    throw new RuntimeException("No Class Loader Defined");
  }

  protected function _getClassLoader()
  {
    if($this->_classLoader === null)
    {
      foreach(spl_autoload_functions() as list($loader))
      {
        if($loader instanceof ClassLoader)
        {
          $this->_classLoader = $loader;
          break;
        }
      }
    }
    return $this->_classLoader;
  }

  public function store()
  {
    return $this->_resourceStore;
  }

  public function config()
  {
    return $this->_config;
  }

  public function calculateRelativePath($filePath)
  {
    return ltrim(str_replace($this->_projectRoot, '', $filePath), '/\\');
  }

}
