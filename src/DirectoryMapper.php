<?php
namespace Packaged\Dispatch;

use Packaged\Config\Provider\ConfigSection;
use Packaged\Helpers\ValueAs;

class DirectoryMapper
{
  protected $_workingDirectory;
  protected $_config;
  protected $_hashMap;

  protected static $_pathCache;

  const MAP_VENDOR = 'v';
  const MAP_SOURCE = 's';
  const MAP_ALIAS  = 'a';
  const MAP_ASSET  = 'p';

  public function __construct($workingDirectory, ConfigSection $dispatchConfig)
  {
    $this->_workingDirectory = $workingDirectory;
    $this->_config           = $dispatchConfig;
  }

  /**
   * Populate the hash map used for hashmap lookups
   *
   * @param array $hashMap
   *
   * @return $this
   */
  public function setHashMap(array $hashMap)
  {
    $this->_hashMap = $hashMap;
    return $this;
  }

  /**
   * Convert a request path to a directory path
   *
   * @param $path
   *
   * @return null|string
   */
  public function urlToPath($path)
  {
    $parts     = explode('/', $path);
    $partCount = count($parts);

    if($partCount > 4 && $parts[0] == self::MAP_ALIAS)
    {
      //Handle Alias URLs
      // a/alias/domain/b/filehash/filepath
      $pathHash = 'b';
      $filename = array_slice($parts, 5);
      $base     = $this->aliasPath($parts);
    }
    else if($partCount > 3 && $parts[0] == self::MAP_SOURCE)
    {
      //Handle Source Paths
      // s/domain/pathHash/fileHash/filePath
      $pathHash = $parts[2];
      $filename = array_slice($parts, 4);
      $base     = $this->sourcePath($parts);
    }
    else if($partCount > 5 && $parts[0] == self::MAP_VENDOR)
    {
      //Handle Vendor Paths
      // v/vendor/package/domain/pathHash/fileHash/filePath
      $pathHash = $parts[4];
      $filename = array_slice($parts, 6);
      $base     = $this->vendorPath($parts);
    }
    else if($partCount > 3 && $parts[0] == self::MAP_ASSET)
    {
      //Handle Asset Paths
      // p/domain/pathHash/fileHash/filePath
      $pathHash = $parts[2];
      $filename = array_slice($parts, 4);
      $base     = $this->assetPath($parts);
    }
    else
    {
      return null;
    }

    return $this->processDirHash($base, $pathHash, $filename);
  }

  /**
   * Convert a vendor provider to a path
   *
   * @param $parts
   *
   * @return null|string
   */
  public function vendorPath($parts)
  {
    list($vendor, $package) = array_slice($parts, 1, 2);
    return build_path('vendor', $vendor, $package);
  }

  /**
   * Convert a source directory file to a path
   *
   * @return null|string
   */
  public function sourcePath()
  {
    return $this->_config->getItem('source_dir', 'src');
  }

  /**
   * Convert an asset dir to path
   *
   * @return null|string
   */
  public function assetPath()
  {
    return $this->_config->getItem('assets_dir', 'assets');
  }

  /**
   * Convert an alias URL to the correct path
   *
   * @param $parts
   *
   * @return null|string
   */
  public function aliasPath($parts)
  {
    $check   = $parts[1];
    $aliases = ValueAs::arr($this->_config->getItem('aliases'));
    if(isset($aliases[$check]))
    {
      return $aliases[$check];
    }
    return null;
  }

  /**
   * Find the correct nested directory
   *
   * @param $base
   * @param $pathHash
   * @param $url
   *
   * @return null|string
   */
  public function processDirHash($base, $pathHash, $url)
  {
    $path = $this->findPathFromHash(
      build_path($this->_workingDirectory, $base),
      $pathHash
    );

    if($path === null)
    {
      return null;
    }

    return build_path($path, implode(DIRECTORY_SEPARATOR, $url));
  }

  /**
   * Create a hash of the directories for a url
   *
   * @param array $parts
   * @param int   $hashLength
   *
   * @return string
   */
  public function hashDirectoryArray(array $parts, $hashLength = 5)
  {
    $result = [];
    foreach($parts as $part)
    {
      //Build up the hash for each part
      $result[] = substr($part, 0, 2) . substr(md5($part), 0, $hashLength);
    }
    return implode(';', $result);
  }

  /**
   * Go on the filesystem search for the matching directory
   *
   * @param $base
   * @param $hash
   *
   * @return null
   */
  public function findPathFromHash($base, $hash)
  {
    //If the requesting path is the base, return the base
    if($hash === 'b')
    {
      return $base;
    }

    //Attempt to load the resource from disk
    if(isset(static::$_pathCache[func_get_arg(0) . $hash]))
    {
      return static::$_pathCache[func_get_arg(0) . $hash];
    }

    $hashParts = explode(';', $hash);
    foreach($hashParts as $part)
    {
      //Search for directories matching the hash
      $dirs = glob(build_path($base, substr($part, 0, 2) . '*'), GLOB_ONLYDIR);
      if(!$dirs)
      {
        return null;
      }

      //If we only found one folder, lets assume its correct
      if(!isset($dirs[1]))
      {
        $base = $dirs[0];
        continue;
      }

      //Marker to ensure a valid directory has been located
      $found = false;

      //Loop over the directories to match the path
      foreach($dirs as $path)
      {
        $folder = [substr($path, strlen($base) + 1)];
        if($part == $this->hashDirectoryArray($folder, strlen($part) - 2))
        {
          $base  = $path;
          $found = true;
          break;
        }
      }

      //The directory could not be found, so just give up
      if(!$found)
      {
        return null;
      }
    }

    //Cache the path to stop future lookups on disk
    $this->cachePath(func_get_arg(0), $hash, $base);

    //Path finally matched
    return $base;
  }

  /**
   * @param $base
   * @param $hash
   * @param $destination
   */
  public function cachePath($base, $hash, $destination)
  {
    //There is no point in caching the base
    if($hash != 'b')
    {
      static::$_pathCache[$base . $hash] = $destination;
    }
  }
}
