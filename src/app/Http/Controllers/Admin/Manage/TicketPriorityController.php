<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Enums\Admin\Manage\CatalogVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Manage\TicketPriorityRequest;
use App\Models\TicketPriority;
use App\Services\Admin\Manage\TicketPriorityCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketPriorityController extends Controller
{
    public function __construct(private readonly TicketPriorityCatalogService $catalog) {}

    public function index(Request $request): Response
    {
        $query = $request->string('status')->value() === 'archived'
            ? TicketPriority::onlyTrashed()
            : TicketPriority::query();
        $query->withCount('tickets')->orderBy('sort_order')->orderBy('name');
        $query->when($request->string('search')->trim()->value(), fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")));
        $query->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->string('visibility')->value()));
        $query->when($request->string('status')->value(), function ($q, $status) {
            match ($status) {
                'active' => $q->whereNull('deleted_at')->where('is_active', true),
                'inactive' => $q->whereNull('deleted_at')->where('is_active', false),
                'archived' => null,
                default => null,
            };
        });

        return Inertia::render('Admin/Manage/Priorities/Index', ['priorities' => $query->paginate(15)->withQueryString(), 'filters' => $request->only(['search', 'status', 'visibility'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Manage/Priorities/Form', ['visibilityOptions' => array_column(CatalogVisibility::cases(), 'value')]);
    }

    public function store(TicketPriorityRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated(), $request->user());

        return to_route('admin.manage.priorities.index')->with('success', 'Priority created.');
    }

    public function edit(TicketPriority $priority): Response
    {
        return Inertia::render('Admin/Manage/Priorities/Form', ['priority' => $priority, 'visibilityOptions' => array_column(CatalogVisibility::cases(), 'value')]);
    }

    public function update(TicketPriorityRequest $request, TicketPriority $priority): RedirectResponse
    {
        $this->catalog->update($priority, $request->validated(), $request->user());

        return to_route('admin.manage.priorities.index')->with('success', 'Priority updated.');
    }

    public function enabled(Request $request, TicketPriority $priority): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);
        $this->catalog->setActive($priority, $request->boolean('enabled'), $request->user());

        return back()->with('success', 'Priority status updated.');
    }

    public function makeDefault(Request $request, TicketPriority $priority): RedirectResponse
    {
        $this->catalog->makeDefault($priority, $request->user());

        return back()->with('success', 'Default priority updated.');
    }

    public function destroy(Request $request, TicketPriority $priority): RedirectResponse
    {
        $this->catalog->archive($priority, $request->user());

        return back()->with('success', 'Priority archived.');
    }

    public function restore(Request $request, int $priority): RedirectResponse
    {
        $this->catalog->restore($priority, $request->user());

        return back()->with('success', 'Priority restored as inactive.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct', 'exists:ticket_priorities,id']]);
        $this->catalog->reorder($data['ids'], $request->user());

        return back()->with('success', 'Priority order updated.');
    }
}
