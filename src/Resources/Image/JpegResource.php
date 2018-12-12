<?php
namespace Packaged\Dispatch\Resources\Image;

class JpegResource extends AbstractImageResource
{
  public function getExtension()
  {
    return 'jpeg';
  }

  public function getContentType()
  {
    return "image/jpeg";
  }
}
