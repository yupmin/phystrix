# Modern Phystrix

[![Build Status](https://travis-ci.org/yupmin/phystrix.svg)](https://travis-ci.org/yupmin/phystrix)

## Requirement
* PHP 5.6 above
* Modern PHP

### About Phystrix

In distributed systems with PHP frontend, application talks to a number of remote services. Be it a set of services of your own, a 3rd party RESTful API or a legacy component that requires networking interaction: in complex, high-load systems occasional failure cannot be avoided.

Phystrix protects the points of access to remote resources by keeping track of various metrics and preventing repetitive failures.

In case of a service failing way too often, to not make the situation worse, Phystrix will temporarily stop issuing requests to it. When the service comes back to life, Phystrix allows the client application to access it again.

And I fixed more for Modern PHP 
* Use PHP 5.6 above
* Deleted 'Zend DI' and User 'PSR DI'
* DI is optional. (for using Your framework)
* PSR2 is default.

### Understanding Phystrix

Not only Phystrix was heavily inspired by the amazing [Hystrix library](https://github.com/Netflix/Hystrix) for Java by Netflix, it also attempts to follow the best practices set by the library. You will notice that configuration parameters are the same as well as much of how it works internally.

Even though there is not much available at the moment in terms of documentation for Phystrix, you can also use [Hystrix wiki](https://github.com/Netflix/Hystrix/wiki) as an additional source of information, to understand how something works etc.

## Installation

Recommended way to install Phystrix is by using [Composer](https://getcomposer.org):

```json
  "require": {
     "yupmin/modern-phystrix": "~3.0"
  },

```

To store and share metrics between requests, Phystrix uses [APC](http://php.net/manual/en/book.apc.php), so make sure you have the PHP extension enabled.

### Php 7.2

In php 7 the API for `apcu` changed. You will need to install [apcu-bc](https://github.com/krakjoe/apcu-bc) in addition to `apcu` to use Phystrix.
The backwards compatibility layer extension must be loaded AFTER `apcu`.

## Usage

To protect a point of access to remote service, we use the [command pattern](http://en.wikipedia.org/wiki/Command_pattern). Here is how a minimal implementation could look like:

```php
use Odesk\Phystrix\AbstractCommand;

/**
 * All commands must extends Phystrix's AbstractCommand
 */
class MyCommand extends AbstractCommand
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * This function is called internally by Phystrix, only if the request is allowed
     *
     * @return mixed
     */
    protected function run()
    {
        return 'Hello ' . $this->name;
    }
}
```

To have the command preconfigured with Phystrix-specific dependencies, you need to obtain it from a special factory that you share with your objects. For instance, in your controller you would do:

```php
$myCommand = $phystrix->getCommand('MyCommand', 'Alex'); // 'Alex' is passed to MyCommand's constructor
$result = $myCommand->execute();
```

Notice, the extra parameters you pass to the factory’s getCommand method are forwarded to the command’s constructor.

The factory is instantiated as follows:

```php
use Zend\Config\Config;
use Odesk\Phystrix\ApcStateStorage;
use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CommandMetricsFactory;
use Odesk\Phystrix\CommandFactory;

$config = new Config(require 'phystrix-config.php');

$stateStorage = new ApcStateStorage();
$circuitBreakerFactory = new CircuitBreakerFactory($stateStorage);
$commandMetricsFactory = new CommandMetricsFactory($stateStorage);
$builder = new DI\ContainerBuilder();
$container = $builder->build();

$phystrix = new CommandFactory(
    $config,
    $circuitBreakerFactory, 
    $commandMetricsFactory,
    new \Odesk\Phystrix\RequestCache(), 
    new \Odesk\Phystrix\RequestLog(),
    $container
);
```

The way you store the configuration files is up to you. Phystrix relies on [Zend\Config](https://github.com/zendframework/Component_ZendConfig)  to manage configurations. In this case, __phystrix-config.php__ is a PHP array:

```php
return array(
    'default' => array( // Default command configuration
        'fallback' => array(
            // Whether fallback logic of the phystrix command is enabled
            'enabled' => true,
        ),
        'circuitBreaker' => array(
            // Whether circuit breaker is enabled, if not Phystrix will always allow a request
            'enabled' => true,
            // How many failed request it might be before we open the circuit (disallow consecutive requests)
            'errorThresholdPercentage' => 50,
            // If true, the circuit breaker will always be open regardless the metrics
            'forceOpen' => false,
            // If true, the circuit breaker will always be closed, allowing all requests, regardless the metrics
            'forceClosed' => false,
            // How many requests we need minimally before we can start making decisions about service stability
            'requestVolumeThreshold' => 10,
            // For how long to wait before attempting to access a failing service
            'sleepWindowInMilliseconds' => 5000,
        ),
        'metrics' => array(
            // This is for caching metrics so they are not recalculated more often than needed
            'healthSnapshotIntervalInMilliseconds' => 1000,
            // The period of time within which we the stats are collected
            'rollingStatisticalWindowInMilliseconds' => 1000,
            // The more buckets the more precise and actual the stats and slower the calculation.
            'rollingStatisticalWindowBuckets' => 10,
        ),
        'requestCache' => array(
            // Request cache, if enabled and a command has getCacheKey implemented
            // caches results within current http request
            'enabled' => true,
        ),
        'requestLog' => array(
            // Request log collects all commands executed within current http request
            'enabled' => false,
        ),
    ),
    'MyCommand' => array( // Command specific configuration
        'fallback' => array(
            'enabled' => false
        )
    )
);
```

Command-specific configurations are merged with the default one on instantiation. “MyCommand” in this case is the command key. By default it is the same as command’s class, but you can set it yourself by overriding the __getCommandKey__ protected method:

```php
    /**
     * This function defines the command key to use for this command
     *
     * @return string
     */
    protected function getCommandKey()
    {
        return 'CustomCommandKey';
    }
```

Phystrix only works with the command keys. If you have two different commands with the same command key - Phystrix will collect metrics, disable and enable requests, as for a single entity. This may be used for grouping commands.

Sometimes, you may need to change a parameter when a command is used in a particular context:

```php
use Zend\Config\Config;
$myCommand = $phystrix->getCommand('MyCommand', 'Alex');
$myCommand->setConfig(new Config(array('requestCache' => array('enabled' => false))));
$result = $myCommand->execute();
```

Note, the config you set is merged with the previously set value.

## Features

### Fallback

For a command, you can specify fallback logic, that will be executed in case of a failure, or when the remote service is blocked:

```php
class GetAvatarUrlCommand extends AbstractCommand
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    protected function run()
    {
        $remoteAvatarService = $this->container->get('avatarService');
        return $remoteAvatarService->getUrlByUser($this->user);
    }

    /**
     * When __run__ fails for some reason, or when Phystrix doesn't allow the request in the first place,
     * this function result will be returned instead
     *
     * @return string
     */
    protected function getFallback()
    {
        // we failed getting user's picture, so showing a generic no-photo placeholder instead.
        return 'http://example/avatars/no-photo.jpg';
    }
}
```

If you want to use logic requiring networking for your fallback, make sure to “wrap” it into a Phystrix command of its own.

### Request cache

Request cache, when enabled, caches command execution result __within a single HTTP request__, so you don’t have to worry about loading data over network more than needed.

Results are cached per command key per cache key. To define cache key generation logic, implement __getCacheKey__ protected method:

```php
    protected function getCacheKey()
    {
        return 'cache_' . $this->user;
    }
```

### Timeout

[Hystrix for Java](https://github.com/Netflix/Hystrix) allows you to set specific time a command is allowed to run. What it does is it limits the time for the thread a command is running in. In PHP we cannot do that, however, as we only have the context of one, current, thread.

Suggested approach is to manually configure the timeout in the library used to access the remote service.

Let’s say you have this Phystrix configuration for MyCommand:

```php
    'MyCommand' => array(
        'fallback' => array(
            'enabled' => false
        ),
        'timeout' => 2000, // milliseconds
    )
```

where “timeout” is a custom parameter which Phystrix does not make any use of. You can specify any arbitrary parameters in Phystrix configuration and they will be available for you in the commands:

```php
    protected function run()
    {
        $remoteAvatarService = $this->container->get('avatarService');
        return $remoteAvatarService->getUrlByUser($this->user);
    }

    /**
     * Custom preparation logic, preceding command execution
     */
    protected function prepare()
    {
        $remoteAvatarService = $this->container->get('avatarService');
        if ($this->config->__isset('timeout')) {
            // if the timeout is exceeded an exception will be thrown
            $remoteAvatarService->setTimeout($this->config->get('timeout'));
        }
    }
```

where the client might be a 3rd library you downloaded, or an instance of http client from a framework such as Zend Framework or Symfony or something you wrote yourself.

Of course, having to add this into each command would be suboptimal. Normally, you will have a set of abstract commands, specific to your use cases. E.g. you might have __GenericCurlCommand__ or __GenericGoogleApiCommand__ and __MyCommand__ would extend one of those.

### Custom dependencies

Since you get the commands from a special factory, you need a way to inject custom dependencies into your commands, such as an instance of HTTP client.

One way would be to extend the __Odesk\Phystrix\CommandFactory__, create your own factory and have it inject what you need.

Alternatively, configure the container instance that __Odesk\Phystrix\CommandFactory__ accepts in the constructor.

The service container can be anything, implementing the very basic [PSR Container](https://github.com/php-fig/container). You can inject an IoC container that will lazily instantiate instance as they are needed, or you can use a simpler, preconfigured, instance of __Psr\Container\ContainerInterface__:

```php
$builder = new DI\ContainerBuilder();
$container = $builder->build();

// autowiring
// new GoogleApi(...);

$phystrix = new CommandFactory(
    $config, 
    $circuitBreakerFactory,
    $commandMetricsFactory, 
    new \Odesk\Phystrix\RequestCache(), 
    new \Odesk\Phystrix\RequestLog(),
    $conatiner
);
```

You can access the service container from within your commands as follows:

```php
    protected function run()
    {
        $googleApi = $this->container->get('googleApi');
        return $googleApi->fetchAllEmail();
    }
```

### Request Log

A useful feature for performance monitoring. When enabled, allows you to retrieve the list of commands executed during the current HTTP request:

```php
/** @var RequestLog $requestLog */
$commands = $requestLog->getExecutedCommands();
```

What you get is an array of actual command instances. For each command you can get the execution time in milliseconds:

```php
$command->getExecutionTimeInMilliseconds();
```

and the list of events, such as "SUCCESS", "FAILURE", "TIMEOUT", "SHORT_CIRCUITED", "FALLBACK_SUCCESS", "FALLBACK_FAILURE", "EXCEPTION_THROWN", "RESPONSE_FROM_CACHE":

```php
$command->getExecutionEvents();
```

### Hystrix Turbine and Dashboard Support

TBD

### Licence

Copyright 2013-2017 Upwork Global Inc. All Rights Reserved.

Phystrix is licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
