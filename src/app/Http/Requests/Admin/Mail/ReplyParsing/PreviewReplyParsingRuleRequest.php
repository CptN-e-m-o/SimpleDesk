<?php

namespace App\Http\Requests\Admin\Mail\ReplyParsing;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use Illuminate\Validation\Rule;

class PreviewReplyParsingRuleRequest extends ReplyParsingRuleRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'test_content' => ['present', 'string', 'max:100000'],
            'test_content_type' => [
                'required',
                Rule::in([
                    ReplyParsingContentType::PlainText->value,
                    ReplyParsingContentType::Html->value,
                ]),
            ],
        ];
    }
}
