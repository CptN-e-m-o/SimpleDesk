<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Database\Factories\Admin\WorkScheduleAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkScheduleAssignment extends Model
{
    /** @use HasFactory<WorkScheduleAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['work_schedule_id', 'user_id', 'effective_from', 'effective_until', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['effective_from' => 'immutable_date', 'effective_until' => 'immutable_date'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id')->withTrashed();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(WorkScheduleException::class);
    }
}
