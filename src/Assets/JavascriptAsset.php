<?php
namespace Packaged\Dispatch\Assets;

use JShrink\Minifier;
use Packaged\Helpers\ValueAs;

class JavascriptAsset extends AbstractDispatchableAsset
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

    try
    {
      return Minifier::minify($data);
    }
    catch(\Exception $e)
    {
      return $data;
    }
  }
}
