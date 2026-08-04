<?php

namespace App\Models\Admin\Mail;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Enums\Admin\Mail\MailProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailProviderConnection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'provider',
        'auth_type',
        'account_identifier',
        'tenant_identifier',
        'configuration',
        'secret_configuration',
        'scopes',
        'token_expires_at',
        'connected_at',
        'last_refreshed_at',
        'is_active',
        'health_status',
        'last_checked_at',
        'last_success_at',
        'last_error_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $hidden = [
        'secret_configuration',
    ];

    protected function casts(): array
    {
        return [
            'provider' => MailProvider::class,
            'auth_type' => MailAuthenticationType::class,
            'configuration' => 'array',
            'secret_configuration' => 'encrypted:array',
            'scopes' => 'array',
            'token_expires_at' => 'immutable_datetime',
            'connected_at' => 'immutable_datetime',
            'last_refreshed_at' => 'immutable_datetime',
            'is_active' => 'boolean',
            'health_status' => MailboxHealthStatus::class,
            'last_checked_at' => 'immutable_datetime',
            'last_success_at' => 'immutable_datetime',
            'last_error_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function channels(): HasMany
    {
        return $this->hasMany(
            MailboxChannel::class,
            'provider_connection_id'
        );
    }

    public function publicConfiguration(): array
    {
        $value = $this->getAttribute('configuration');

        return is_array($value) ? $value : [];
    }

    public function secrets(): array
    {
        $value = $this->getAttribute('secret_configuration');

        return is_array($value) ? $value : [];
    }

    public function dateAttribute(string $key): ?CarbonImmutable
    {
        $value = $this->getAttribute($key);

        return $value instanceof CarbonImmutable ? $value : null;
    }
}
