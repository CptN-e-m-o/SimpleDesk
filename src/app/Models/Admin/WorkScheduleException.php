<?php

namespace App\Models\Admin;

use App\Enums\Admin\WorkScheduleExceptionType;
use Database\Factories\Admin\WorkScheduleExceptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkScheduleException extends Model
{
    /** @use HasFactory<WorkScheduleExceptionFactory> */
    use HasFactory;

    protected $fillable = ['work_schedule_assignment_id', 'date', 'type', 'reason', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'type' => WorkScheduleExceptionType::class];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(WorkScheduleAssignment::class, 'work_schedule_assignment_id');
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(WorkScheduleExceptionInterval::class)->orderBy('sort_order');
    }
}
