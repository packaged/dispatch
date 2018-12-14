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

    //Find all URL(.*) and dispatch their values
    $this->_content = preg_replace_callback(
      '~url\(\s*[\'"]?([^\s\'"]*?)[\'"]?\s*\)~',
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
    // if url path is empty, return unchanged
    if(empty($uri[1]))
    {
      return $uri[0];
    }

    list($path, $append) = Strings::explode('?', $uri[1], [$uri[1], null], 2);

    if(!$this->_manager->isExternalUrl($path))
    {
      $path = Path::system($this->makeFullPath(dirname($path), dirname($this->_path)), basename($path));
    }

    $url = $this->_manager->getResourceUri($path);

    if(empty($url))
    {
      return "url('" . ($path ?? $uri[0]) . "')";
    }

    if(!empty($append))
    {
      return "url('$url?$append')";
    }
    return "url('$url')";
  }

  /**
   * Make the relative path
   *
   * @param $relativePath
   * @param $workingDirectory
   *
   * @return string
   */
  protected function makeFullPath($relativePath, $workingDirectory)
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
      if($levelUps > count($workingDirectoryParts))
      {
        //Relative to this directory is not allowed
        return null;
      }
      return implode('/', array_slice($workingDirectoryParts, $levelUps)) . $relativePath;
    }
    return $relativePath;
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
