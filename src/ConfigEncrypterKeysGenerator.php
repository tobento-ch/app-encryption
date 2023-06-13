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

namespace Tobento\App\Encryption;

use Tobento\App\AppInterface;
use Tobento\Service\Config\DataInterface;
use Tobento\Service\Encryption\KeyGeneratorInterface;
use Tobento\Service\Migration\Action\FileStringReplacer;
use Tobento\Service\Migration\ActionFailedException;

/**
 * Generates keys and updates config file.
 */
class ConfigEncrypterKeysGenerator
{
    /**
     * Create a new ConfigEncrypterKeysGenerator.
     *
     * @param AppInterface $app
     */
    public function __construct(
        protected AppInterface $app,
    ) {}
    
    /**
     * Generates keys.
     *
     * @param DataInterface $data
     * @return DataInterface
     */
    public function generate(DataInterface $data): DataInterface
    {
        $config = $data->data();
        $encrypters = $config['encrypters'] ?? [];
        $replace = [];

        foreach($encrypters as $name => $encrypter) {
            if (
                !isset($encrypter['config']['key'])
                || !str_starts_with($encrypter['config']['key'], '{')
            ) {
                continue;
            }
            
            if (!isset($encrypter['keyGenerator'])) {
                continue;
            }
            
            $key = $encrypter['config']['key'];
            $keyGenerator = $encrypter['keyGenerator'];
            $keyGenerator = $this->app->make($keyGenerator);

            if (! $keyGenerator instanceof KeyGeneratorInterface) {
                continue;
            }
            
            $replace[$key] = $keyGenerator->generateKey();
            $config['encrypters'][$name]['config']['key'] = $replace[$key];
        }
        
        if (empty($replace)) {
            return $data;
        }
        
        $replacer = new FileStringReplacer(
            file: $data->file(),
            replace: $replace,
        );
        
        try {
            $replacer->process();
            return $data->withData($config);
        } catch (ActionFailedException $e) {
            return $data;
        }
    }
}