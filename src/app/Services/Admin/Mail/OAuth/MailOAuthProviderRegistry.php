<?php

namespace App\Services\Admin\Mail\OAuth;

use App\Contracts\Admin\Mail\OAuth\MailOAuthProvider;
use App\Enums\Admin\Mail\MailProvider;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthConfigurationException;

class MailOAuthProviderRegistry
{
    private array $providers = [];

    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            if ($provider instanceof MailOAuthProvider) {
                $this->providers[$provider->provider()->value] = $provider;
            }
        }
    }

    public function resolve(MailProvider|string $provider): MailOAuthProvider
    {
        $key = $provider instanceof MailProvider ? $provider->value : $provider;

        return $this->providers[$key]
            ?? throw new MailOAuthConfigurationException("OAuth provider [{$key}] is not supported.");
    }
}
