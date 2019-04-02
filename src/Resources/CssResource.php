<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Helpers\Strings;

class CssResource extends AbstractDispatchableResource
{
  protected $_options = [
    'minify'   => true,
    'dispatch' => true,
  ];

  public function getExtension()
  {
    return 'css';
  }

  public function getContentType()
  {
    return "text/css";
  }

  protected function _dispatch()
  {
    // find all URL(.*) and dispatch their values
    $this->_content = preg_replace_callback(
      '~(url\(\s*)(["\']?)(.*?)\2(\s*\))~',
      [$this, "_dispatchUrlPaths"],
      $this->_content
    );
    // find all @import
    $this->_content = preg_replace_callback(
      '~(@import\s*)(["\']?)(.*?)\2(\s*;)?~',
      [$this, "_dispatchUrlPaths"],
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
  protected function _dispatchUrlPaths($uri)
  {
    return $uri[1] . Strings::wrap($this->_getDispatchUrl($uri[3]), $uri[2], true) . $uri[4];
  }

  protected function _minify()
  {
    // Remove comments.
    $this->_content = preg_replace('@/\*.*?\*/@s', '', $this->_content);

    // Remove whitespace around symbols.
    $this->_content = preg_replace('@\s*([{}:;,])\s*@', '\1', $this->_content);

    // Remove unnecessary semicolons.
    $this->_content = preg_replace('@;}@', '}', $this->_content);

    // Replace #rrggbb with #rgb when possible.
    $this->_content = preg_replace('@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3@i', '#\1\2\3', $this->_content);
    $this->_content = trim($this->_content);
  }
}
