<?php

namespace App\Models\Admin;

use App\Enums\Admin\Weekday;
use Database\Factories\Admin\WorkScheduleIntervalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleInterval extends Model
{
    /** @use HasFactory<WorkScheduleIntervalFactory> */
    use HasFactory;

    protected $fillable = ['work_schedule_id', 'day_of_week', 'starts_at', 'ends_at', 'ends_next_day', 'sort_order'];

    protected function casts(): array
    {
        return ['day_of_week' => Weekday::class, 'ends_next_day' => 'boolean'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }
}
