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

namespace Tobento\App\Encryption\Boot\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Encryption\Boot\Encryption;
use Tobento\Service\Encryption\EncryptersInterface;
use Tobento\Service\Encryption\EncrypterInterface;
use Tobento\Service\Config\ConfigInterface;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Boot\Config;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Migration\Action\FileStringReplacer;
    
/**
 * EncryptionTest
 */
class EncryptionTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../../'), 'root')
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config')
            ->dir($app->dir('root').'vendor', 'vendor')
            // for testing only we add public within app dir.
            ->dir($app->dir('app').'public', 'public');
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfacesAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(Encryption::class);
        $app->booting();
        
        $this->assertInstanceof(EncryptersInterface::class, $app->get(EncryptersInterface::class));
        $this->assertInstanceof(EncrypterInterface::class, $app->get(EncrypterInterface::class));
        $this->assertInstanceof(EncryptersInterface::class, $app->get(Encryption::class)->encrypters());
        $this->assertInstanceof(EncrypterInterface::class, $app->get(Encryption::class)->encrypter());        
    }
    
    public function testKeysAreGenerated()
    {
        $app = $this->createApp();
        $app->boot(Encryption::class);
        $app->booting();
        
        $keys = $this->fetchKeys($app);
        $defaultKey = $keys['default'] ?? '';
        
        $this->assertFalse(str_starts_with($defaultKey, '{default-encrypt-key}'));
        $this->assertTrue(strlen($defaultKey) > 50);
    }
    
    public function testKeysAreRegenerated()
    {
        $app = $this->createApp();
        $app->boot(Encryption::class);
        $app->booting();
        
        $keys = $this->fetchKeys($app);
        $defaultKey = $keys['default'] ?? '';
        
        $this->assertFalse(str_starts_with($defaultKey, '{default-encrypt-key}'));
        $this->assertTrue(strlen($defaultKey) > 50);
        
        $appNew = $this->createApp(deleteDir: false);
        $appNew->boot(Config::class);
        $appNew->booting();
        
        $data = $appNew->get(ConfigInterface::class)->data('encryption.php');
        
        (new FileStringReplacer(
            file: $data->file(),
            replace: [
                $defaultKey => '{new-encrypt-key}',
            ],
        ))->process();
        
        $appNew->boot(Encryption::class);
        $appNew->booting();
        
        $keys = $this->fetchKeys($appNew);
        $defaultKeyNew = $keys['default'] ?? '';
        
        $this->assertFalse(str_starts_with($defaultKeyNew, '{new-encrypt-key}'));
        $this->assertTrue(strlen($defaultKeyNew) > 50);
        $this->assertFalse($defaultKey === $defaultKeyNew);
    }

    public function testKeysAreNotRegeneratedIfKeyIsGenerated()
    {
        $app = $this->createApp();
        $app->boot(Encryption::class);
        $app->booting();
        
        $keys = $this->fetchKeys($app);
        $defaultKey = $keys['default'] ?? '';
        
        $this->assertFalse(str_starts_with($defaultKey, '{default-encrypt-key}'));
        $this->assertTrue(strlen($defaultKey) > 50);
        
        $appNew = $this->createApp(deleteDir: false);        
        $appNew->boot(Encryption::class);
        $appNew->booting();
        
        $keys = $this->fetchKeys($appNew);
        $defaultKeyNew = $keys['default'] ?? '';
        
        $this->assertSame($defaultKey, $defaultKeyNew);
    }
    
    public function testKeysAreNotRegeneratedIfDisabled()
    {
        $app = $this->createApp();
        $app->boot(Encryption::class);
        $app->booting();
        
        $keys = $this->fetchKeys($app);
        $defaultKey = $keys['default'] ?? '';
        
        $this->assertFalse(str_starts_with($defaultKey, '{default-encrypt-key}'));
        $this->assertTrue(strlen($defaultKey) > 50);
        
        $appNew = $this->createApp(deleteDir: false);
        $appNew->boot(Config::class);
        $appNew->booting();
        
        $data = $appNew->get(ConfigInterface::class)->data('encryption.php');
        
        (new FileStringReplacer(
            file: $data->file(),
            replace: [
                '\'keyGenerationEnabled\' => true,' => '\'keyGenerationEnabled\' => false,',
                $defaultKey => '{new-encrypt-key}',
            ],
        ))->process();
        
        $appNew->boot(Encryption::class);
        $appNew->booting();
        
        $keys = $this->fetchKeys($appNew);
        $defaultKeyNew = $keys['default'] ?? '';
        
        $this->assertSame('{new-encrypt-key}', $defaultKeyNew);
    }
    
    protected function fetchKeys($app)
    {
        $data = $app->get(ConfigInterface::class)->data('encryption.php');
        $config = $data->data();
        $encrypters = $config['encrypters'] ?? [];
        $keys = [];

        foreach($encrypters as $name => $encrypter) {
            if (isset($encrypter['config']['key'])) {
                $keys[$name] = $encrypter['config']['key'];
            }
        }
        
        return $keys;
    }
}