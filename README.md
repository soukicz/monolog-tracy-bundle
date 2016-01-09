# nella/monolog-tracy-bundle

[![Build Status](https://img.shields.io/travis/nella/monolog-tracy-bundle/master.svg?style=flat-square)](https://travis-ci.org/nella/monolog-tracy-bundle)
[![Code Coverage](https://img.shields.io/coveralls/nella/monolog-tracy-bundle.svg?style=flat-square)](https://coveralls.io/r/nella/monolog-tracy-bundle)
[![SensioLabsInsight Status](https://img.shields.io/sensiolabs/i/76c87979-7eda-4f6b-94a5-07bd54259d5f.svg?style=flat-square)](https://insight.sensiolabs.com/projects/76c87979-7eda-4f6b-94a5-07bd54259d5f)
[![Latest Stable Version](https://img.shields.io/packagist/v/nella/monolog-tracy-bundle.svg?style=flat-square)](https://packagist.org/packages/nella/monolog-tracy-bundle)
[![Composer Downloads](https://img.shields.io/packagist/dt/nella/monolog-tracy-bundle.svg?style=flat-square)](https://packagist.org/packages/nella/monolog-tracy-bundle)
[![Dependency Status](https://img.shields.io/versioneye/d/user/projects/569191a8daa0bf00330000db.svg?style=flat-square)](https://www.versioneye.com/user/projects/569191a8daa0bf00330000db)
[![HHVM Status](https://img.shields.io/hhvm/nella/monolog-tracy-bundle.svg?style=flat-square)](http://hhvm.h4cc.de/package/nella/monolog-tracy-bundle)


Bundle providing mainly integration of [Tracy](https://github.com/nette/tracy) into [Symfony](https://symfony.com).

## Tracy capabilities

Long story short, Tracy helps you debug your applications when an error occurs providing you lots of information about what just happened. Check out
[live example](http://nette.github.io/tracy/tracy-exception.html) and [Tracy documentation](https://github.com/nette/tracy#visualization-of-errors-and-exceptions)
to see the full power of this tool.

To replace default Symfony Bluescreen you can use [Tracy Bluescreen Bundle](https://github.com/VasekPurchart/Tracy-Blue-Screen-Bundle)
fully compatible with this library.

## Installation

Using  [Composer](http://getcomposer.org/):

```sh
$ composer require nella/monolog-tracy-bundle:~0.1.0
```

### Register Bundle
```php
// AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Nella\MonologTracyBundle\MonologTracyBundle(), // what a terrible name!
    );
}
```

### Register a new Monolog handler
```yml
monolog:
    handlers:
        blueScreen:
            type: blue screen
```

## Profit!
Any error/exception making it to the top is automatically saved in `%kernel.logs_dir%/blueScreen`. You can easily change the log directory,
see full configuration options below:

```yml
# config.yml
monolog:
    handlers:
        blueScreen:
            type: blue screen
            path: %kernel.logs_dir%/blueScreen # must exist
            level: debug
            bubble: true
```
This works out of the box and also in production mode!

## Tips

### Log notices/warnings in production

Use Symfony parameter `debug.error_handler.throw_at`: (see http://php.net/manual/en/function.error-reporting.php for possible values)
```yml
parameters:
    debug.error_handler.throw_at: -1
```

### Using Tracy\Debugger::dump

To prevent forgotten dumps to appear on production you can simply change the mode like this:
```php
// AppKernel.php

use Tracy\Debugger;

public function __construct($environment, $debug)
{
    Debugger::$productionMode = $environment === 'prod';
    parent::__construct($environment, $debug);
}
```

