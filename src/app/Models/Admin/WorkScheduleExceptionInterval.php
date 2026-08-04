<?php

namespace App\Models\Admin;

use Database\Factories\Admin\WorkScheduleExceptionIntervalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleExceptionInterval extends Model
{
    /** @use HasFactory<WorkScheduleExceptionIntervalFactory> */
    use HasFactory;

    protected $fillable = ['work_schedule_exception_id', 'starts_at', 'ends_at', 'ends_next_day', 'sort_order'];

    protected function casts(): array
    {
        return ['ends_next_day' => 'boolean'];
    }

    public function exception(): BelongsTo
    {
        return $this->belongsTo(WorkScheduleException::class, 'work_schedule_exception_id');
    }
}
