<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Encryption\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Encryption\ConfigEncrypterKeysGenerator;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Encryption\EncrypterInterface;
use Tobento\Service\Encryption\EncryptersInterface;
use Tobento\Service\Encryption\LazyEncrypters;

/**
 * Encryption
 */
class Encryption extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads encryption config file',
            '(re)generates encryption keys for config file',
            'implements encryption interfaces based on encryption config',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(
        Config $config,
        Migration $migration,
    ): void {
        // install encryption config:
        $migration->install(\Tobento\App\Encryption\Migration\Encryption::class);
        
        // handle encryption config:
        $data = $this->app->get(ConfigInterface::class)->data('encryption.php');
        $config = $data->data();
        
        if ($config['keyGenerationEnabled'] ?? false) {
            $data = (new ConfigEncrypterKeysGenerator($this->app))->generate(data: $data);
            $config = $data->data();
        }
        
        // set interfaces:
        $this->app->set(EncryptersInterface::class, function() use ($config): EncryptersInterface {
            return new LazyEncrypters(
                container: $this->app->container(),
                encrypters: $config['encrypters'] ?? [],
            );
        });
        
        $this->app->set(EncrypterInterface::class, function() use ($config): EncrypterInterface {
            $name = $config['default'] ?? 'default';
            return $this->app->get(EncryptersInterface::class)->get($name);
        });
    }

    /**
     * Returns the encrypter.
     *
     * @return EncrypterInterface
     */
    public function encrypter(): EncrypterInterface
    {
        return $this->app->get(EncrypterInterface::class);
    }
    
    /**
     * Returns the encrypters.
     *
     * @return EncryptersInterface
     */
    public function encrypters(): EncryptersInterface
    {
        return $this->app->get(EncryptersInterface::class);
    }
}