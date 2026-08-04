<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Database\Factories\Admin\WorkScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSchedule extends Model
{
    /** @use HasFactory<WorkScheduleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'timezone', 'is_active', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'deleted_at' => 'immutable_datetime'];
    }

    public function intervals(): HasMany
    {
        return $this->hasMany(WorkScheduleInterval::class)->orderBy('day_of_week')->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkScheduleAssignment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVisible($query, string $status = 'active')
    {
        return match ($status) {
            'archived' => $query->onlyTrashed(),
            'inactive' => $query->whereNull('deleted_at')->where('is_active', false),
            'all' => $query->withTrashed(),
            default => $query->whereNull('deleted_at')->where('is_active', true),
        };
    }
}
