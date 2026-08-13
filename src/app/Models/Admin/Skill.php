<?php

namespace App\Models\Admin;

use App\Enums\Admin\Skills\SkillMatchType;
use App\Models\User\User;
use Database\Factories\Admin\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'match_type', 'is_active', 'sort_order',
        'version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'match_type' => SkillMatchType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'version' => 'integer',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(SkillRule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeArchived($query)
    {
        return $query->onlyTrashed();
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
        }));
    }
}
