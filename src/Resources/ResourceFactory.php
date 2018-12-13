<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Dispatch\Resources\Font\AfmResource;
use Packaged\Dispatch\Resources\Font\DfontResource;
use Packaged\Dispatch\Resources\Font\EotResource;
use Packaged\Dispatch\Resources\Font\OpenTypeResource;
use Packaged\Dispatch\Resources\Font\PfaResource;
use Packaged\Dispatch\Resources\Font\PfbResource;
use Packaged\Dispatch\Resources\Font\PfmResource;
use Packaged\Dispatch\Resources\Font\TtcResource;
use Packaged\Dispatch\Resources\Font\TtfResource;
use Packaged\Dispatch\Resources\Font\WoffResource;
use Packaged\Dispatch\Resources\Image\GifResource;
use Packaged\Dispatch\Resources\Image\IconResource;
use Packaged\Dispatch\Resources\Image\JpegResource;
use Packaged\Dispatch\Resources\Image\JpgResource;
use Packaged\Dispatch\Resources\Image\PngResource;
use Packaged\Dispatch\Resources\Image\SvgResource;
use Packaged\Dispatch\Resources\Video\FlvResource;
use Packaged\Dispatch\Resources\Video\Mp4Resource;
use Packaged\Dispatch\Resources\Video\MpegResource;
use Packaged\Dispatch\Resources\Video\QuicktimeResource;
use Packaged\Dispatch\Resources\Video\WebmResource;
use Symfony\Component\HttpFoundation\Response;

class ResourceFactory
{
  private static $_resourceMap = [
    'js'    => JavascriptResource::class,
    'json'  => JsonResource::class,
    'css'   => CssResource::class,
    'swf'   => FlashResource::class,
    'pdf'   => PdfResource::class,
    'zip'   => ZipResource::class,
    'gif'   => GifResource::class,
    'ico'   => IconResource::class,
    'jpeg'  => JpegResource::class,
    'jpg'   => JpgResource::class,
    'png'   => PngResource::class,
    'svg'   => SvgResource::class,
    'flv'   => FlvResource::class,
    'mp4'   => Mp4Resource::class,
    'mpeg'  => MpegResource::class,
    'mov'   => QuicktimeResource::class,
    'webm'  => WebmResource::class,
    'afm'   => AfmResource::class,
    'dfont' => DfontResource::class,
    'eot'   => EotResource::class,
    'otf'   => OpenTypeResource::class,
    'pfa'   => PfaResource::class,
    'pfb'   => PfbResource::class,
    'pfm'   => PfmResource::class,
    'ttc'   => TtcResource::class,
    'ttf'   => TtfResource::class,
    'woff'  => WoffResource::class,
  ];

  public static function getExtensions()
  {
    return array_keys(self::$_resourceMap);
  }

  public static function addExtension($ext, $classname)
  {
    if(is_object($classname))
    {
      $classname = get_class($classname);
    }
    self::$_resourceMap[$ext] = $classname;
  }

  /**
   * @param $extension
   *
   * @return Resource
   */
  public static function getExtensionResource($extension): Resource
  {
    $extension = strtolower($extension);
    if(isset(self::$_resourceMap[$extension]))
    {
      return new self::$_resourceMap[$extension]();
    }
    return new UnknownResource();
  }

  /**
   * @param Resource $resource
   *
   * @return Response
   * @throws \Exception
   */
  public static function create(Resource $resource)
  {
    $response = new Response();

    //Set the correct content type based on the Resource
    $response->headers->set('Content-Type', $resource->getContentType());
    $response->headers->set('X-Content-Type-Options', 'nosniff');

    //Ensure the cache varies on the encoding
    //Domain specific content will vary on the uri itself
    $response->headers->set("Vary", "Accept-Encoding");

    //Set the etag to the hash of the request uri, as it is in itself a hash
    $response->setEtag($resource->getHash());
    $response->setPublic();

    //This resource should last for 30 days in cache
    $response->setMaxAge(2592000);
    $response->setSharedMaxAge(2592000);
    $response->setExpires((new \DateTime())->add(new \DateInterval('P30D')));

    //Set the last modified date to now
    $date = new \DateTime();
    $date->setTimezone(new \DateTimeZone('UTC'));
    $response->headers->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
    $response->setContent($resource->getContent());
    return $response;
  }
}