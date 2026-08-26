<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use Throwable;

class BroadcastClientConfigurationService
{
    public function __construct(private readonly BroadcastDriverRegistry $registry) {}

    public function effective(): array
    {
        $settings = BroadcastDriverSettings::query()->find(BroadcastDriverSettings::SINGLETON_ID);
        if (! $settings || $settings->mode === BroadcastConfigurationMode::Deployment) {
            return ['available' => false, 'ownership' => 'deployment', 'message' => 'Deployment client metadata is not managed by SimpleDesk.'];
        }
        try {
            $configuration = BroadcastDriverConfiguration::query()->findOrFail($settings->active_configuration_id);

            return ['ownership' => 'managed', ...$this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration)->client];
        } catch (Throwable) {
            return ['available' => false, 'ownership' => 'managed', 'message' => 'Managed client metadata is unavailable.'];
        }
    }
}
