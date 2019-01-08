<?php
namespace Packaged\Dispatch\Resources;

use JShrink\Minifier;

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

  protected function _minify()
  {
    try
    {
      $this->_content = Minifier::minify($this->_content);
    }
    catch(\Exception $e)
    {
    }
  }
}
