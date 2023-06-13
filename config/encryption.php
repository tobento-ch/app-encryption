<?php
/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\Service\Encryption\EncrypterFactoryInterface;
use Tobento\Service\Encryption\KeyGeneratorInterface;
use Tobento\Service\Encryption\Crypto;

return [

    /*
    |--------------------------------------------------------------------------
    | Key Generation Enabled
    |--------------------------------------------------------------------------
    |
    | If set to true it (re)generates new keys if specified like
    | 'key' => '{any-name}' in the Encrypters section.
    |
    | On production you may set it to false.
    |
    */

    'keyGenerationEnabled' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Default Encrypter Name
    |--------------------------------------------------------------------------
    |
    | Specify the default encrypter name you wish to use for your application.
    | The name must match any encrypters name below.
    |
    */

    'default' => 'default',

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
                'key' => '{default-encrypt-key}',
            ],
            
            // must implement KeyGeneratorInterface
            'keyGenerator' => Crypto\KeyGenerator::class,
        ],

    ],
    
];