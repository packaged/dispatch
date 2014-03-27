<?php
namespace Packaged\Dispatch;

use Packaged\Config\Provider\ConfigSection;
use Packaged\Dispatch\Assets\IDispatchableAsset;
use Packaged\Helpers\ValueAs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Dispatch implements HttpKernelInterface
{
  protected $_app;
  protected $_config;
  protected $_baseDirectory;
  protected $_hashTable;

  const EVENT_RESOURCE_GENERATE = 'resource.generate';

  /**
   * @var EventDispatcher
   */
  protected static $dispatcher;

  public function __construct(HttpKernelInterface $app, $options)
  {
    if($options instanceof ConfigSection)
    {
      $config = $options;
    }
    else
    {
      $config = new ConfigSection('dispatch', (array)$options);
    }

    $this->_app    = $app;
    $this->_config = $config;
  }

  /**
   * Get the dispatch config
   *
   * @return ConfigSection
   */
  public function getConfig()
  {
    return $this->_config;
  }

  /**
   * Set the directory where all paths should start
   *
   * @param $directory
   *
   * @return $this
   */
  public function setBaseDirectory($directory)
  {
    $this->_baseDirectory = $directory;
    return $this;
  }

  /**
   * Get the base directory for paths to start from
   *
   * @return mixed
   */
  public function getBaseDirectory()
  {
    return $this->_baseDirectory;
  }

  public function prepare()
  {
    return $this;
  }

  public function setFileHashTable(array $hash)
  {
    $this->_hashTable = $hash;
    return $this;
  }

  public function addFileHashEntry($file, $hash)
  {
    $this->_hashTable[$file] = $hash;
    return $this;
  }

  public function getFileHash($key)
  {
    return isset($this->_hashTable[$key]) ? $this->_hashTable[$key] : null;
  }

  /**
   * Handles a Request to convert it to a Response.
   *
   * When $catch is true, the implementation must catch all exceptions
   * and do its best to convert them to a Response instance.
   *
   * @param Request $request  A Request instance
   * @param integer $type     The type of the request
   *                          (one of HttpKernelInterface::MASTER_REQUEST
   *                          or HttpKernelInterface::SUB_REQUEST)
   * @param Boolean $catch    Whether to catch exceptions or not
   *
   * @return Response A Response instance
   *
   * @throws \Exception When an Exception occurs during processing
   *
   * @api
   */
  public function handle(
    Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    //Start listening for resource uri generation requests
    $generator          = new ResourceGenerator($this, $request);
    static::$dispatcher = new EventDispatcher();

    //Listen in for resource generate requests
    static::$dispatcher->addListener(
      self::EVENT_RESOURCE_GENERATE,
      [$generator, 'processEvent']
    );

    if($this->isDispatchRequest($request))
    {
      //Process the response for a dispatchable
      return $this->getResponseForPath(
        $this->getDispatchablePath($request),
        $request
      );
    }
    else
    {
      //Handle to request through the next/final app
      return $this->_app->handle($request, $type, $catch);
    }
  }

  /**
   * @param $path
   *
   * @return Response
   */
  public function notFoundResponse($path)
  {
    return new Response($path . ' could not be located', 404);
  }

  /**
   * @return Response
   */
  public function invalidUrlResponse()
  {
    return new Response('The URL you requested appears to be mythical', 400);
  }

  /**
   * Response for unsupported extension
   *
   * @param string $extension
   *
   * @return Response
   */
  public function unsupportedResponse($extension)
  {
    return new Response(
      '*.' . $extension . ' files are not currently unsupported',
      500
    );
  }

  /**
   * Create the response for the given path
   *
   * @param         $path
   * @param Request $request
   *
   * @return Response
   */
  public function getResponseForPath($path, Request $request)
  {
    if(empty($path))
    {
      //What resources do you expect to find with no path?
      return $this->invalidUrlResponse();
    }

    $pathInfo = pathinfo($path);

    //Every dispatch request needs an extension
    if(empty($pathInfo['extension']))
    {
      return $this->invalidUrlResponse();
    }

    $response = new AssetResponse();

    //Grab the correct asset for the requesting extension
    $asset = $response->assetByExtension($pathInfo['extension']);
    if($asset === null)
    {
      return $this->unsupportedResponse($pathInfo['extension']);
    }

    //Load the options
    $options = ValueAs::arr(
      $this->_config->getItem($pathInfo['extension'] . '_config'),
      null
    );

    if($options !== null)
    {
      $asset->setOptions($options);
    }

    //Lookup the full path on the filesystem
    $dirMapper = new DirectoryMapper($this->_baseDirectory, $this->_config);
    $directory = $dirMapper->urlToPath($pathInfo['dirname']);

    $filePath = build_path($directory, $pathInfo['basename']);

    //If the asset does not exist on disk, return a not found error
    if($directory === null || !file_exists($filePath))
    {
      return $this->notFoundResponse($path);
    }

    //Give the asset its file content
    $asset->setContent(file_get_contents($filePath));

    if($asset instanceof IDispatchableAsset)
    {
      //Set the asset manager
      $asset->setAssetManager(AssetManager::buildFromUri($path));
    }

    //Create and return the response
    return $response->createResponse($asset, $request);
  }

  /**
   * Convert the path to the dispatchable part of the path
   *
   * @param Request $request
   *
   * @return string
   */
  public function getDispatchablePath(Request $request)
  {
    $path  = ltrim($request->getPathInfo(), '/');
    $runOn = $this->_config->getItem('run_on', 'path');
    if($runOn == 'path')
    {
      //If we are using a path based url, strip off the identifier
      $match = $this->_config->getItem('run_match', 'res');
      $path  = substr($path, strlen($match) + 1);
    }
    return $path;
  }

  /**
   * Is Dispatch responsible for the incoming request
   *
   * @param Request $request
   *
   * @return bool
   */
  public function isDispatchRequest(Request $request)
  {
    $runOn = $this->_config->getItem('run_on', 'path');
    switch($runOn)
    {
      case 'path':
        $match = $this->_config->getItem('run_match', 'res');
        return starts_with($request->getPathInfo() . '/', "/$match/");
      case 'subdomain':
        $matchCfg   = $this->_config->getItem('run_match', 'static.,assets.');
        $subDomains = ValueAs::arr($matchCfg, ['static.']);
        return starts_with_any($request->getHost(), $subDomains);
      case 'domain':
        $matchCfg = $this->_config->getItem('run_match', null);
        $domains  = ValueAs::arr($matchCfg, []);
        return ends_with_any($request->getHost(), $domains, false);
    };
    return false;
  }

  /**
   * Trigger a dispatch event
   *
   * @param DispatchEvent $event
   * @param string        $eventName
   *
   * @return DispatchEvent
   */
  public static function trigger(
    DispatchEvent $event, $eventName = self::EVENT_RESOURCE_GENERATE
  )
  {
    if(!isset(static::$dispatcher))
    {
      return null;
    }

    return static::$dispatcher->dispatch($eventName, $event);
  }
}
