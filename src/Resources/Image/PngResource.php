<?php
namespace Packaged\Dispatch\Resources\Image;

class PngResource extends AbstractImageResource
{
  public function getExtension()
  {
    return 'png';
  }

  public function getContentType()
  {
    return "image/png";
  }
}
