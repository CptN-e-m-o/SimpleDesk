<?php

namespace App\Models;

use App\Enums\Admin\Manage\CatalogVisibility;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketPriority extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'color', 'visibility', 'sort_order', 'is_default', 'is_active', 'is_system', 'created_by', 'updated_by'];

    protected $casts = ['visibility' => CatalogVisibility::class, 'is_default' => 'boolean', 'is_active' => 'boolean', 'is_system' => 'boolean'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
