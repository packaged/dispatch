Dispatch
========

Asset Management Middleware for Stack PHP


Basic Usage
===

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
