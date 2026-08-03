<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Enums\Admin\Mail\ReplyParsingPatternType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\ReplyParsing\PreviewReplyParsingRuleRequest;
use App\Http\Requests\Admin\Mail\ReplyParsing\StoreReplyParsingRuleRequest;
use App\Http\Requests\Admin\Mail\ReplyParsing\UpdateReplyParsingRuleRequest;
use App\Models\Admin\Mail\ReplyParsingRule;
use App\Services\Admin\Mail\ReplyParsing\ReplyParsingService;
use App\Services\Admin\Mail\Settings\ReplyParsingRuleAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReplyParsingRuleController extends Controller
{
    public function __construct(
        private readonly ReplyParsingRuleAdminService $rules,
        private readonly ReplyParsingService $parser,
    ) {}

    public function index(): Response
    {
        $rules = ReplyParsingRule::query()
            ->withTrashed()
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ReplyParsingRule $rule): array => $this->ruleData($rule))
            ->values();

        return Inertia::render('Admin/Email/ReplyParsing/Index', [
            'rules' => $rules,
            'summary' => [
                'total' => $rules->count(),
                'active' => $rules->where('is_active', true)->where('is_deleted', false)->count(),
                'disabled' => $rules->where('is_active', false)->where('is_deleted', false)->count(),
                'deleted' => $rules->where('is_deleted', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Email/ReplyParsing/Create', $this->formOptions());
    }

    public function store(StoreReplyParsingRuleRequest $request): RedirectResponse
    {
        $this->rules->create($request->validated());

        return redirect()
            ->route('admin.email.reply-parsing.index')
            ->with('success', 'Reply parsing rule was created successfully.');
    }

    public function edit(int $rule): Response
    {
        $rule = ReplyParsingRule::query()->findOrFail($rule);

        return Inertia::render('Admin/Email/ReplyParsing/Edit', [
            ...$this->formOptions(),
            'rule' => $this->ruleData($rule),
        ]);
    }

    public function update(
        UpdateReplyParsingRuleRequest $request,
        int $rule,
    ): RedirectResponse {
        $rule = ReplyParsingRule::query()->findOrFail($rule);
        $this->rules->update($rule, $request->validated());

        return redirect()
            ->route('admin.email.reply-parsing.index')
            ->with('success', 'Reply parsing rule was updated successfully.');
    }

    public function destroy(int $rule): RedirectResponse
    {
        $rule = ReplyParsingRule::query()->findOrFail($rule);
        $this->rules->delete($rule);

        return back()->with('success', 'Reply parsing rule was deleted.');
    }

    public function restore(int $rule): RedirectResponse
    {
        $this->rules->restore($rule);

        return back()->with('success', 'Reply parsing rule was restored and remains disabled.');
    }

    public function forceDestroy(int $rule): RedirectResponse
    {
        $this->rules->forceDelete($rule);

        return back()->with('success', 'Reply parsing rule was permanently deleted.');
    }

    public function preview(PreviewReplyParsingRuleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rule = new ReplyParsingRule([
            'name' => trim($data['name']),
            'pattern' => $data['pattern'],
            'pattern_type' => $data['pattern_type'],
            'content_type' => $data['content_type'],
            'display_order' => (int) $data['display_order'],
            'is_active' => true,
            'description' => null,
        ]);

        $result = $this->parser->parse(
            $data['test_content'],
            ReplyParsingContentType::from($data['test_content_type']),
            [$rule],
        );

        return response()->json([
            'data' => [
                ...$result->toArray(),
                'pattern_type' => $rule->pattern_type->value,
                'content_type' => $rule->content_type->value,
            ],
        ]);
    }

    private function ruleData(ReplyParsingRule $rule): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'pattern' => $rule->pattern,
            'pattern_type' => $rule->pattern_type->value,
            'content_type' => $rule->content_type->value,
            'display_order' => $rule->display_order,
            'is_active' => $rule->is_active,
            'description' => $rule->description,
            'is_deleted' => $rule->trashed(),
            'deleted_at' => $rule->deleted_at?->toIso8601String(),
            'created_at' => $rule->created_at?->toIso8601String(),
            'updated_at' => $rule->updated_at?->toIso8601String(),
        ];
    }

    private function formOptions(): array
    {
        return [
            'patternTypes' => array_map(
                static fn (ReplyParsingPatternType $type): array => [
                    'value' => $type->value,
                    'label' => $type === ReplyParsingPatternType::Literal ? 'Literal' : 'Regular expression',
                ],
                ReplyParsingPatternType::cases(),
            ),
            'contentTypes' => array_map(
                static fn (ReplyParsingContentType $type): array => [
                    'value' => $type->value,
                    'label' => match ($type) {
                        ReplyParsingContentType::PlainText => 'Plain text',
                        ReplyParsingContentType::Html => 'HTML',
                        ReplyParsingContentType::Both => 'Plain text and HTML',
                    },
                ],
                ReplyParsingContentType::cases(),
            ),
        ];
    }
}
