<?php

namespace MCordingley\LaravelSapient;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use MCordingley\LaravelSapient\Console\GenerateSealingKeyPair;
use MCordingley\LaravelSapient\Console\GenerateSharedAuthenticationKey;
use MCordingley\LaravelSapient\Console\GenerateSharedEncryptionKey;
use MCordingley\LaravelSapient\Console\GenerateSigningKeyPair;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Sapient\CryptographyKeys\SealingPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SealingSecretKey;
use ParagonIE\Sapient\CryptographyKeys\SharedAuthenticationKey;
use ParagonIE\Sapient\CryptographyKeys\SharedEncryptionKey;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SigningSecretKey;

final class SapientServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        $this->bindKey(SealingPublicKey::class, 'sapient.sealing.public_key')
            ->bindKey(SealingSecretKey::class, 'sapient.sealing.private_key')
            ->bindKey(SharedAuthenticationKey::class, 'sapient.shared.authentication_key')
            ->bindKey(SharedEncryptionKey::class, 'sapient.shared.encryption_key')
            ->bindKey(SigningPublicKey::class, 'sapient.signing.public_key')
            ->bindKey(SigningSecretKey::class, 'sapient.signing.private_key');
    }

    /**
     * @param string $concrete
     * @param string $configKey
     * @return SapientServiceProvider
     */
    private function bindKey(string $concrete, string $configKey): self
    {
        /** @var Repository $config */
        $config = $this->app->make('config');

        $this->app->when($concrete)
            ->needs('$key')
            ->give(function () use ($config, $configKey) {
                return Base64UrlSafe::decode($config->get($configKey));
            });

        return $this;
    }

    /**
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSealingKeyPair::class,
                GenerateSharedAuthenticationKey::class,
                GenerateSharedEncryptionKey::class,
                GenerateSigningKeyPair::class,
            ]);
        }

        $source = realpath($raw = __DIR__ . '/config.php') ?: $raw;

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('sapient.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('sapient');
        }

        $this->mergeConfigFrom($source, 'sapient');
    }
}
