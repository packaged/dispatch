<?php
namespace Packaged\Dispatch;

use Packaged\Config\Provider\ConfigSection;
use Packaged\Helpers\ValueAs;

class DirectoryMapper
{
  protected $_workingDirectory;
  protected $_config;
  protected $_hashMap;

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

    //Handle Fully Hash URLs
    if($partCount > 2 && $parts[0] == 'h')
    {
      return $this->urlToHashedPath($parts);
    }

    //Handle Alias URLs
    if($partCount > 3 && $parts[0] == 'a')
    {
      return $this->urlToAliasPath($parts);
    }

    //Handle Source URLs
    if($partCount > 2 && $parts[0] == 's')
    {
      return $this->urlToSourcePath($parts);
    }

    //Handle Vendor URLs
    if($partCount > 4 && $parts[0] == 'v')
    {
      return $this->urlToVendorPath($parts);
    }

    //Handle Asset URLs
    if($partCount > 2 && $parts[0] == 'p')
    {
      return $this->urlToAssetPath($parts);
    }

    return null;
  }

  /**
   * Convert a hash cache url to a pth
   *
   * @param $parts
   *
   * @return null|string
   */
  public function urlToHashedPath($parts)
  {
    if(isset($this->_hashMap[$parts[1]]))
    {
      return build_path_custom(
        DIRECTORY_SEPARATOR,
        [$this->_hashMap[$parts[1]]] + array_slice($parts, 2)
      );
    }
    return null;
  }

  /**
   * Convert a vendor provider to a path
   *
   * @param $parts
   *
   * @return null|string
   */
  public function urlToVendorPath($parts)
  {
    list($vendor, $package) = array_slice($parts, 1, 2);
    return $this->processDirHash(
      build_path('vendor', $vendor, $package),
      $parts[4],
      array_slice($parts, 5)
    );
  }

  /**
   * Convert a source directory file to a path
   *
   * @param $parts
   *
   * @return null|string
   */
  public function urlToSourcePath($parts)
  {
    return $this->processDirHash(
      $this->_config->getItem('source_dir', 'src'),
      $parts[2],
      array_slice($parts, 3)
    );
  }

  /**
   * Convert an asset dir to path
   *
   * @param $parts
   *
   * @return null|string
   */
  public function urlToAssetPath($parts)
  {
    return $this->processDirHash(
      $this->_config->getItem('assets_dir', 'assets'),
      $parts[2],
      array_slice($parts, 3)
    );
  }

  /**
   * Convert an alias URL to the correct path
   *
   * @param $parts
   *
   * @return null|string
   */
  public function urlToAliasPath($parts)
  {
    $check   = $parts[1];
    $aliases = ValueAs::arr($this->_config['aliases']);
    foreach($aliases as $alias => $path)
    {
      if($alias == $check)
      {
        return build_path(
          $this->_workingDirectory,
          $path,
          array_slice($parts, 3)
        );
      }
    }
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

    return build_path($path, implode('DS', $url));
  }

  /**
   * Create a hash of the directories for a url
   *
   * @param     $parts
   * @param int $hashLength
   *
   * @return string
   */
  public function hashDirectoryArray($parts, $hashLength = 5)
  {
    $result = [];
    foreach($parts as $part)
    {
      $result[] = substr($part, 0, 1) .
        substr(md5($part), 0, $hashLength);
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
    $hashParts = explode(';', $hash);
    foreach($hashParts as $part)
    {
      //Search for directories matching the hash
      $dirs = glob(build_path($base, substr($part, 0, 1) . '*'), GLOB_ONLYDIR);
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
        if($part == $this->hashDirectoryArray($folder, strlen($part) - 1))
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

    //Path finally matched
    return $base;
  }
}
