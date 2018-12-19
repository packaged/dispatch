<?php
namespace Packaged\Dispatch\Resources;

use JShrink\Minifier;
use Packaged\Helpers\ValueAs;

class JavascriptResource extends AbstractDispatchableResource
{
  protected $_options = [
    'minify' => 'true',
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
    $file = basename($this->_filePath);

    if(strpos($file, '.min.') > 0 || strpos($file, '-min.') > 0)
    {
      return $data;
    }

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
