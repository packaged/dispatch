<?php
namespace Packaged\Dispatch\Resources\Image;

class SvgResource extends AbstractImageResource
{
  public function getExtension()
  {
    return 'svg';
  }

  public function getContentType()
  {
    return "image/svg+xml";
  }
}
