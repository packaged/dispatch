<?php
namespace Packaged\Dispatch\Assets;

use Packaged\Helpers\ValueAs;

class JavascriptAsset extends AbstractAsset
{
  protected $_options = [
    'minify' => 'true'
  ];

  public function getExtension()
  {
    return 'js';
  }

  public function getContentType()
  {
    return "text/javascript";
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

    //Strip Comments
    $data = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
    $data = preg_replace('!^(?:[\t ]+)?\/\/(?:.*)?$!m', '', $data);

    //remove tabs, spaces, newlines, etc.
    $data = str_replace(array("\t"), ' ', $data);
    $data = str_replace(
      array("\r\n", "\r", "\n", '  ', '    ', '    '),
      '',
      $data
    );

    return $data;
  }
}
