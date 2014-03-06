<?php
namespace Packaged\Dispatch;

use Packaged\Helpers\ValueAs;
use Symfony\Component\HttpFoundation\Request;

class ResourceGenerator
{
  /**
   * @var Dispatch
   */
  protected $_dispatcher;
  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $_request;

  /**
   * Cache for base paths
   *
   * @var array
   */
  protected $_baseHash;

  /**
   * Micro Optimisation for calling HttpHost on Request
   *
   * @var string
   */
  protected $_httpHost;

  /**
   * @var DirectoryMapper
   */
  protected $_mapper;

  /**
   * @param Dispatch $dispatcher
   * @param Request  $request
   */
  public function __construct(Dispatch $dispatcher, Request $request)
  {
    $this->_dispatcher = $dispatcher;
    $this->_request    = $request;
    $this->_httpHost   = $this->_request->getHttpHost();
    $this->_mapper     = new DirectoryMapper(
      $dispatcher->getBaseDirectory(), $dispatcher->getConfig()
    );
    $this->_mapper->setHashMap(
      ValueAs::arr($dispatcher->getConfig()->getItem('hashtable', []))
    );
  }

  /**
   * @param DispatchEvent $event
   */
  public function processEvent(DispatchEvent $event)
  {
    //Generate the URI and return it in the event
    $uri = $this->generateUriPath(
      $event->getMapType(),
      $event->getLookupParts(),
      $this->_httpHost,
      $event->getPath(),
      $event->getFilename()
    );

    //Invalid resource requested
    if($uri === null)
    {
      $event->setResult(null);
      return;
    }

    $cfg = $this->_dispatcher->getConfig();
    switch($cfg->getItem('run_on', 'path'))
    {
      case 'path':
        $path = $cfg->getItem('run_match', 'res');
        $uri  = build_path_unix($this->_httpHost, $path, $uri);
        break;
      case 'subdomain':
        $sub     = $cfg->getItem('run_match', 'static.');
        $domainP = explode('.', $this->_httpHost);
        if(count($domainP) > 2)
        {
          $domain = implode('.', array_slice($domainP, 1));
        }
        else
        {
          $domain = implode('.', $domainP);
        }

        $uri = build_path_unix($sub . $domain, $uri);
        break;
      case 'domain':
        $domain = $cfg->getItem('run_match', $this->_httpHost);
        $uri    = build_path_unix($domain, $uri);
        break;
    }

    $event->setResult('//' . $uri);
  }

  /**
   * Generate the URI for the provided details
   *
   * @param $type
   * @param $lookup
   * @param $domain
   * @param $path
   * @param $file
   *
   * @return null|string
   */
  public function generateUriPath($type, $lookup, $domain, $path, $file)
  {
    $parts = [];

    //Include the map type
    $parts[] = $type;

    //When lookup parts are avilable, e.g. vendor/package, include them
    if(is_array($lookup))
    {
      foreach($lookup as $lookupPart)
      {
        $parts[] = $lookupPart;
      }
    }

    $parts[] = static::hashDomain($domain);

    //If not path is available, assume you are requesting the base path
    if(empty($path))
    {
      $partHash = 'b';
    }
    else
    {
      //Build the hashable path
      $partHash = $this->_mapper->hashDirectoryArray(
        ValueAs::arr(explode('/', $path))
      );
    }

    $parts[] = $partHash;

    $baseDir = $this->getBasePath($this->_mapper, $type, (array)$lookup);

    $filePath = build_path($baseDir, $path, $file);
    $fileHash = $this->_dispatcher->getFileHash($filePath);
    if($fileHash === null)
    {
      //File hash doesnt exist in the cache, so lets look it up
      $fullPath = build_path($this->_dispatcher->getBaseDirectory(), $filePath);
      $fileHash = ResourceGenerator::getFileHash($fullPath);
      if(!$fileHash)
      {
        //If we cant get a hash of the file, its unlikely it exists
        return null;
      }
      //Cache the entry, to optimise should the same resource be re-requested
      $this->_dispatcher->addFileHashEntry($filePath, $fileHash);
    }

    $parts[] = substr($fileHash, 0, 7);

    //Include the file extension
    $parts[] = $file;

    return implode('/', $parts);
  }

  /**
   * Get the base directory for the type/lookup info provided
   *
   * @param DirectoryMapper $mapper
   * @param                 $type
   * @param array           $lookup
   *
   * @return null|string
   */
  public function getBasePath(DirectoryMapper $mapper, $type, array $lookup)
  {
    $cache = $type . '-' . implode('.', $lookup);

    //If the path is cached, return it
    if(isset($this->_baseHash[$cache]))
    {
      return $this->_baseHash[$cache];
    }

    $parts = array_merge([$type], $lookup);
    switch($type)
    {
      case DirectoryMapper::MAP_ALIAS:
        $this->_baseHash[$cache] = $mapper->aliasPath($parts);
        break;
      case DirectoryMapper::MAP_SOURCE:
        $this->_baseHash[$cache] = $mapper->sourcePath();
        break;
      case DirectoryMapper::MAP_ASSET:
        $this->_baseHash[$cache] = $mapper->assetPath();
        break;
      case DirectoryMapper::MAP_VENDOR:
        $this->_baseHash[$cache] = $mapper->vendorPath($parts);
        break;
    }

    //Return the cache
    return isset($this->_baseHash[$cache]) ? $this->_baseHash[$cache] : null;
  }

  /**
   * Hash the domain, for a unique path, allowing for domain switching on
   * resources.  Allowing for ott caching
   *
   * @param $domain
   *
   * @return string
   */
  public static function hashDomain($domain)
  {
    return substr(md5($domain), 0, 5);
  }

  /**
   * Hash the file content from the disk
   *
   * @param $path
   *
   * @return string
   */
  public static function getFileHash($path)
  {
    if(!file_exists($path))
    {
      return null;
    }

    return md5_file($path);
  }
}
