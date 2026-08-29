<?php

namespace App\Http\Requests\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;
use App\Enums\Admin\System\QueueHealthStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueDriverConfigurationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:255'], 'driver' => ['nullable', Rule::enum(QueueDriverType::class)], 'state' => ['nullable', Rule::in(['enabled', 'disabled'])], 'archived' => ['nullable', Rule::in(['active', 'archived', 'all'])], 'health' => ['nullable', Rule::enum(QueueHealthStatus::class)]];
    }
}
