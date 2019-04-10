<?php
namespace Packaged\Dispatch\Resources;

use JShrink\Minifier;
use Packaged\Helpers\Strings;

class JavascriptResource extends AbstractDispatchableResource
{
  protected $_options = [
    'dispatch' => true,
    'minify'   => true,
  ];

  public function getExtension()
  {
    return 'js';
  }

  public function getContentType()
  {
    return "text/javascript";
  }

  protected function _dispatch()
  {
    $this->_content = preg_replace_callback(
      '~(import(?:\s+.+?\s+from)?\s*)(["\']?)(.+?)\2(.*?)~',
      [$this, "_dispatchImportUrls"],
      $this->_content
    );
  }

  /**
   * Dispatch a nested URL
   *
   * @param $uri
   *
   * @return string
   * @throws \Exception
   */
  protected function _dispatchImportUrls($uri)
  {
    return $uri[1] . Strings::wrap($this->_getDispatchUrl($uri[3]), $uri[2], true) . $uri[4];
  }

  protected function _makeFullPath($relativePath, $workingDirectory)
  {
    $path = parent::_makeFullPath($relativePath, $workingDirectory);
    if(!file_exists($this->_manager->getFilePath($path)))
    {
      $ext = pathinfo($this->_path, PATHINFO_EXTENSION);
      if(file_exists($this->_manager->getFilePath($path . '.' . $ext)))
      {
        return $path . '.' . $ext;
      }
    }
    return $path;
  }

  protected function _minify()
  {
    try
    {
      $this->_content = Minifier::minify($this->_content);
    }
    catch(\Exception $e)
    {
      //If minification doesnt work, return the original content
    }
  }
}
