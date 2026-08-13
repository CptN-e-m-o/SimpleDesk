<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillRule extends Model
{
    protected $fillable = ['subject_type', 'field_key', 'operator', 'value', 'sort_order'];

    protected function casts(): array
    {
        return ['value' => 'array', 'sort_order' => 'integer'];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
