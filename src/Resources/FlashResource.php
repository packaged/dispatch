<?php
namespace Packaged\Dispatch\Resources;

class FlashResource extends AbstractResource
{
  public function getExtension()
  {
    return 'swf';
  }

  public function getContentType()
  {
    return "application/x-shockwave-flash";
  }
}
