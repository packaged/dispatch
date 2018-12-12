<?php
namespace Packaged\Dispatch\Resources\Image;

class IconResource extends AbstractImageResource
{
  public function getExtension()
  {
    return 'ico';
  }

  public function getContentType()
  {
    return "image/x-icon";
  }
}
