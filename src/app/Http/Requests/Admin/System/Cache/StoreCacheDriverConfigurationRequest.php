<?php
namespace App\Http\Requests\Admin\System\Cache;
use Illuminate\Foundation\Http\FormRequest;
class StoreCacheDriverConfigurationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255'], 'driver' => ['required', 'string', 'max:50'], 'infrastructure_connection_id' => ['nullable', 'integer', 'min:1'], 'configuration' => ['nullable', 'array'], 'configuration.infrastructure_connection_id' => ['prohibited'], 'configuration.path' => ['prohibited'], 'configuration.lock_path' => ['prohibited'], 'is_enabled' => ['required', 'boolean']]; }
}
