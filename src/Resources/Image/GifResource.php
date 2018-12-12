<?php
namespace Packaged\Dispatch\Resources\Image;

class GifResource extends AbstractImageResource
{
  public function getExtension()
  {
    return 'gif';
  }

  public function getContentType()
  {
    return "image/gif";
  }
}
