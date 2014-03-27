<?php
namespace Packaged\Dispatch\Assets;

use Packaged\Helpers\ValueAs;

class CssAsset extends AbstractDispatchableAsset
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
    $data = parent::getContent();

    //Return the raw content if minification has been disabled
    if(!ValueAs::bool($this->getOption('minify', true)))
    {
      return $data;
    }

    //Do not minify scripts containing the @do-not-minify
    if(strpos($data, '@' . 'do-not-minify') !== false)
    {
      return $data;
    }

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
