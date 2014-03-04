<?php
namespace Packaged\Dispatch\Assets;

use Packaged\Helpers\ValueAs;

class CssAsset extends AbstractAsset
{
  protected $_options = [
    'minify' => 'true'
  ];

  public function getExtension()
  {
    return 'css';
  }

  public function getContentType()
  {
    return "text/css";
  }

  public function getContent()
  {
    //Return the raw content if minification has been disabled
    if(!ValueAs::bool($this->getOption('minify', true)))
    {
      return $this->_content;
    }

    //Do not minify scripts containing the @do-not-minify
    if(strpos($this->_content, '@' . 'do-not-minify') !== false)
    {
      return $this->_content;
    }

    $data = $this->_content;

    // Remove comments.
    $data = preg_replace('@/\*.*?\*/@s', '', $data);

    // Remove whitespace around symbols.
    $data = preg_replace('@\s*([{}:;,])\s*@', '\1', $data);

    // Remove unnecessary semicolons.
    $data = preg_replace('@;}@', '}', $data);

    // Replace #rrggbb with #rgb when possible.
    $data = preg_replace(
      '@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3@i',
      '#\1\2\3',
      $data
    );

    return trim($data);
  }
}
