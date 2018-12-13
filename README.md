# Dispatch

[![Latest Stable Version](https://poser.pugx.org/packaged/dispatch/version.png)](https://packagist.org/packages/packaged/dispatch)
[![Total Downloads](https://poser.pugx.org/packaged/dispatch/d/total.png)](https://packagist.org/packages/packaged/dispatch)

Resource Management for PHP


## Basic Installation

The following should be included in your public/index.php file

    $dispatchConfig = new \Packaged\Config\Provider\ConfigSection('dispatch');
    $dispatchConfig->addItem('run_on', 'path');
    $dispatchConfig->addItem('run_match', 'assets');
    $dispatchConfig->addItem('aliases', ['ali' => 'src/res']);
    $dispatchConfig->addItem('css_config', ['minify' => 'false']);
    $dispatchConfig->addItem('ext_config', [/*Config Options*/]);

    $dispatcher = new \Packaged\Dispatch\Dispatch($app, $dispatchConfig);
    $dispatcher->setBaseDirectory(dirname(__DIR__));

    //By md5 hashing the files based on the project root, runtime hashes are
    //not required, and will perform much faster
    $dispatcher->setFileHashTable(
      [
        'src/res/css/base.css' => 'd5364e0d4c0174e4a30cea9a03af036d',
        'assets/003.JPG'       => '8c0d1206f71976e45cd138ed30645519'
      ]
    );


You can then add into the request through either

Stack PHP Method

    $app = (new \Stack\Builder())
      ->push([$dispatcher, 'prepare'])
      ->resolve($app);

Raw Call

    $app = $dispatch->handle($request)


### Using Dispatch

There are a few types of asset manager you can use to generate static resource 
uris.  An asset manager defines a route path by which processing should start
 when searching for your specified resources.

#### Config Options

##### run\_on / run_match

These options determine how asset paths are generated.

| run\_on Option | run_match Default | Description                            |
| ------------- | ----------------- | -------------------------------------- |
| path          | res               | //domain.tld/ **res** /*resource_url*    |
| subdomain     | static.           | // **static.** domain.tld/*resource_url* |
| domain        | (current domain)  | // **domain.tld** /*resource_url*        |


#### Asset Type

    $am = \Packaged\Dispatch\AssetManager::assetType();

By default, this type will search in /assets, assuming you want to store your
assets within that folder in the base of your project.  If you want to change
the default assets path, you can use the 'assets_dir' config item, relative to
your project base.


#### Alias Type

    $am = \Packaged\Dispatch\AssetManager::aliasType('alias');

Alias types are used for common paths, which you may want to group assets, 
which could be a deep path within your source or vendor directories.
Aliases can be configured in the config as a keyed array within the 'aliases' 
config item

#### Source Type

    $am = \Packaged\Dispatch\AssetManager::sourceType();

Source type will load data from your source folder.  By default this is 'src'
however, if you store your source files in another directory, this can be
changed using the 'source_dir' config item.

#### Vendor Type

    $am = \Packaged\Dispatch\AssetManager::vendorType('vendor','package');

Vendor type will set the base to a vendors folder specified by composer, this
will usually be /vendor/{vendor}/{package}

#### Automatic Detection

    $am = new \Packaged\Dispatch\AssetManager(new Class());

By passing through a class into the constructor for the asset manager, dispatch
will automatically detect to see if the class is within your source directory
or created by a vendor package.  This will give you either a vendor type or
source type.


### Generating Resource Uris

Now you have your asset manager object, you can generate resource uris to use
within your project.  All you need to do is pass the relative path (from the
asset manager base), to the method 'getResourceUri', and a full uri will be
returned.

    <!--[if lt IE 9]>
    <script src="<?= $am->getResourceUri('javascripts/ie.js'); ?>"></script>
    <![endif]-->
    
### CSS &amp; JS Global Store

For simplicity within your code, you are also able to add css and js files to a
store for the render, which can be pulled out on demand.  This allows you to 
include relevant css or js files based on sub classes, controllers views etc and
in a global layout, simply include any that have been built up.

To include your css files, you just need to call requireCss on your assetManager

    $am->requireCss(
      [
        'stylesheets/bootstrap',
        'stylesheets/theme',
        'stylesheets/widgets',
      ]
    );
    
You can also include css files with options for things like delayed js or print

    $am->requireCss('stylesheets/print',['media' => 'print']);
    
    
To then render the urls out to the page, you have two options.

Option 1. Let dispatch do it all for you

    <?= $am->generateHtmlIncludes('css'); ?>
    
Option 2. Get the uris yourself, with their options, and render them

    $uris = AssetManager::getUrisByType('css');
    //Options is a key value array or options sent through by requireCss
    foreach($uris as $uri => $options)
    {
      echo '<link href="'. $uri .'" rel="stylesheet" type="text/css">';
    }


The same functionality is also available for javascript, by replacing css 
with js in method names and parameters.

We recommed using AssetManager::TYPE_CSS and AssetManager::TYPE_JS within your
code, however, for shorter examples, the string version is also valid.

## Custom Asset Types

Custom assets are used if you need an asset to be dispatched with a specific
mime type, or your asset must be rendered or compiled before being dispatched.
Simply create a class implementing IAsset (you can extend AbstractAsset) and
register it with AssetResponse.
 
The following example will cause all requested files with the extension 'ext' to
be served with the 'application/x-my-asset' content type, and return an MD5 hash
of the file contents.

    class MyAsset extends AbstractAsset
    {
      public function getExtension()
      {
        return 'ext';
      }
    
      public function getContentType()
      {
        return "application/x-my-asset";
      }
      
      public function getContent()
      {
        return md5(parent::getContent());
      }
    }
    
    AssetResponse::addAssetType('ext', '\MyAsset');
