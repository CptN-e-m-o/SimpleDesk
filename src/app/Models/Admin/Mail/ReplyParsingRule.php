<?php

namespace App\Models\Admin\Mail;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Enums\Admin\Mail\ReplyParsingPatternType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $pattern
 * @property ReplyParsingPatternType $pattern_type
 * @property ReplyParsingContentType $content_type
 * @property int $display_order
 * @property bool $is_active
 * @property string|null $description
 */
class ReplyParsingRule extends Model
{
    use SoftDeletes;

    protected $table = 'mail_reply_parsing_rules';

    protected $fillable = [
        'name',
        'pattern',
        'pattern_type',
        'content_type',
        'display_order',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'pattern_type' => ReplyParsingPatternType::class,
            'content_type' => ReplyParsingContentType::class,
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
