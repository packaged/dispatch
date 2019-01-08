<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Dispatch\ResourceManager;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;

abstract class AbstractDispatchableResource extends AbstractResource implements DispatchableResource
{
  /**
   * @var ResourceManager
   */
  protected $_manager;
  protected $_path;
  protected $_workingDirectory;
  protected $_processedContent = false;

  public function setManager(ResourceManager $am)
  {
    $this->_manager = $am;
  }

  public function setProcessingPath($path)
  {
    $this->_path = $path;
    return $this;
  }

  /**
   * Set the resource content
   *
   * @param $content
   *
   * @return Resource
   */
  public function setContent($content)
  {
    $this->_processedContent = false;
    return parent::setContent($content);
  }

  /**
   * Dispatch the raw content
   *
   * @return void
   */
  protected function _processContent()
  {
    //Treat as a standard resource if no resource manager has been set.
    if(!isset($this->_manager))
    {
      return;
    }

    //Do not modify file content
    if(strpos($this->_content, '@' . 'do-not-dispatch') !== false)
    {
      return;
    }

    //Do not modify javascript content
    if($this instanceof JavascriptResource)
    {
      return;
    }

    //Find all URL(.*) and dispatch their values
    $this->_content = preg_replace_callback(
      '~(?<=url\()\s*(["\']?)(.*?)\1\s*(?=\))~',
      [$this, "_dispatchNestedUrl"],
      $this->_content
    );

    //Stop the process from running for every fetch of the content
    $this->_processedContent = true;
  }

  /**
   * Dispatch a nested URL
   *
   * @param $uri
   *
   * @return string
   * @throws \Exception
   */
  protected function _dispatchNestedUrl($uri)
  {
    $quote = $uri[1];
    $path = $uri[2];

    list($path, $append) = Strings::explode('?', $path, [$path, null], 2);

    if(!$this->_manager->isExternalUrl($path))
    {
      $path = Path::system($this->_makeFullPath(dirname($path), dirname($this->_path)), basename($path));
    }

    $url = $this->_manager->getResourceUri($path);

    if(empty($url))
    {
      return $quote . ($path ?? $uri[0]) . $quote;
    }

    if(!empty($append))
    {
      return $quote . "$url?$append" . $quote;
    }
    return $quote . "$url" . $quote;
  }

  /**
   * Make the relative path
   *
   * @param $relativePath
   * @param $workingDirectory
   *
   * @return string
   */
  protected function _makeFullPath($relativePath, $workingDirectory)
  {
    if($relativePath == '.')
    {
      return $workingDirectory;
    }
    $levelUps = substr_count($relativePath, '../');
    if($levelUps > 0)
    {
      $relativePath = str_replace('../', '', $relativePath);
      $workingDirectoryParts = explode('/', $workingDirectory);
      $moves = count($workingDirectoryParts) - $levelUps;
      if($moves < 0)
      {
        //Relative to this directory is not allowed
        return null;
      }
      return Path::custom('/', array_merge(array_slice($workingDirectoryParts, 0, $moves), [$relativePath]));
    }
    return $workingDirectory[0] !== '.' ? Path::url($workingDirectory, $relativePath) : $relativePath;
  }

  /**
   * Get the content for this resource
   *
   * @return mixed
   */
  public function getContent()
  {
    if(!$this->_processedContent)
    {
      $this->_processContent();
    }
    return parent::getContent();
  }
}
