<?php
namespace Packaged\Dispatch\Resources;

class CssResource extends AbstractDispatchableResource
{
  protected $_options = [
    'minify' => 'true',
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
    $regex = '~(?<=url\()\s*(["\']?)(.*?)\1\s*(?=\))~';
    //Find all URL(.*) and dispatch their values
    $this->_content = preg_replace_callback($regex, [$this, "_dispatchNestedUrl"], $this->_content);
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
