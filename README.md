# App Encryption

Encryption support for the app.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Encryption Boot](#encryption-boot)
        - [Encryption Config](#encryption-config)
        - [Encryption Usage](#encryption-usage)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app encryption project running this command.

```
composer require tobento/app-encryption
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Encryption Boot

The encryption boot does the following:

* installs and loads encryption config file
* (re)generates encryption keys for config file
* implements encryption interfaces based on encryption config

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots:
$app->boot(\Tobento\App\Encryption\Boot\Encryption::class);

// Run the app:
$app->run();
```

You may check out the [**Encryption Service**](https://github.com/tobento-ch/service-encryption) to learn more about it.

### Encryption Config

The configuration for the encryption is located in the ```app/config/encryption.php``` file at the default App Skeleton config location where you can specify the encrypters for your application.

**Regenerating encryption keys**

If you want to regenerate encryption keys, simply replace the existing key with ```{any-unique-name}``` in the ```app/config/encryption.php``` file. On the next booting, it will regenerate it automatically.

```php
return [
    
    //...
    
    /*
    |--------------------------------------------------------------------------
    | Encrypters
    |--------------------------------------------------------------------------
    |
    | Specify the encrypters for your application.
    |
    */
    
    'encrypters' => [
        
        'default' => [
            // must implement EncrypterFactoryInterface
            'factory' => Crypto\EncrypterFactory::class,
            
            'config' => [
                // replace existing key:
                'key' => '{default-encrypt-key}',
            ],
            
            // must implement KeyGeneratorInterface
            'keyGenerator' => Crypto\KeyGenerator::class,
        ],

    ],
];
```

### Encryption Usage

You can access the encrypter(s) in several ways:

**Using the app**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Encryption\EncryptersInterface;
use Tobento\Service\Encryption\EncrypterInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots:
$app->boot(\Tobento\App\Encryption\Boot\Encryption::class);
$app->booting();

// using the default encrypter:
$encrypter = $app->get(EncrypterInterface::class);

// using a specified encrypter:
$encrypters = $app->get(EncryptersInterface::class);

$cookieEncrypter = $encrypters->get('cookies');

// you may check if the encrypter exists:
var_dump($encrypters->has('cookies'));
// bool(true)

// encrypt and decrypt:
$encrypted = $encrypter->encrypt('something');
        
$decrypted = $encrypter->decrypt($encrypted);

// Run the app:
$app->run();
```

**Using autowiring**

You can also request the ```EncryptersInterface::class``` or ```EncrypterInterface::class``` in any class resolved by the app.

```php
use Tobento\Service\Encryption\EncryptersInterface;
use Tobento\Service\Encryption\EncrypterInterface;

class SomeService
{
    public function __construct(
        protected EncryptersInterface $encrypters,
        protected EncrypterInterface $encrypter,
    ) {}
}
```

**Using the view boot**

```php
use Tobento\App\Boot;
use Tobento\App\Encryption\Boot\Encryption;

class AnyServiceBoot extends Boot
{
    public const BOOT = [
        // you may ensure the encryption boot.
        Encryption::class,
    ];
    
    public function boot(Encryption $encryption)
    {
        $encrypter = $encryption->encrypter();
        $encrypters = $encryption->encrypters();
    }
}
```

You may check out the [**Encryption Service**](https://github.com/tobento-ch/service-encryption) to learn more about it.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)