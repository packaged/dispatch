<?php
namespace Packaged\Dispatch\Resources\Image;

class WebpResource extends AbstractImageResource
{
  public function getExtension()
  {
    return 'webp';
  }

  public function getContentType()
  {
    return "image/webp";
  }
}
