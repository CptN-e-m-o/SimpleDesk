<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Enums\Admin\Manage\CatalogVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Manage\TicketTypeRequest;
use App\Models\TicketType;
use App\Services\Admin\Manage\TicketTypeCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketTypeController extends Controller
{
    public function __construct(private readonly TicketTypeCatalogService $catalog) {}

    public function index(Request $request): Response
    {
        $query = TicketType::withTrashed()->withCount('tickets')->orderBy('sort_order')->orderBy('name');
        $query->when($request->string('search')->trim()->value(), fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")));
        $query->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->string('visibility')->value()));
        $query->when($request->string('status')->value(), function ($q, $status) {
            match ($status) {
                'active' => $q->whereNull('deleted_at')->where('is_active', true),
                'inactive' => $q->whereNull('deleted_at')->where('is_active', false),
                'archived' => $q->onlyTrashed(),
                default => null,
            };
        });

        return Inertia::render('Admin/Manage/TicketTypes/Index', ['ticketTypes' => $query->paginate(15)->withQueryString(), 'filters' => $request->only(['search', 'status', 'visibility'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Manage/TicketTypes/Form', ['visibilityOptions' => array_column(CatalogVisibility::cases(), 'value')]);
    }

    public function store(TicketTypeRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated(), $request->user());

        return to_route('admin.manage.ticket-types.index')->with('success', 'Ticket type created.');
    }

    public function edit(TicketType $ticketType): Response
    {
        return Inertia::render('Admin/Manage/TicketTypes/Form', ['ticketType' => $ticketType, 'visibilityOptions' => array_column(CatalogVisibility::cases(), 'value')]);
    }

    public function update(TicketTypeRequest $request, TicketType $ticketType): RedirectResponse
    {
        $this->catalog->update($ticketType, $request->validated(), $request->user());

        return to_route('admin.manage.ticket-types.index')->with('success', 'Ticket type updated.');
    }

    public function enabled(Request $request, TicketType $ticketType): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);
        $this->catalog->setActive($ticketType, $request->boolean('enabled'), $request->user());

        return back()->with('success', 'Ticket type status updated.');
    }

    public function destroy(Request $request, TicketType $ticketType): RedirectResponse
    {
        $this->catalog->archive($ticketType, $request->user());

        return back()->with('success', 'Ticket type archived.');
    }

    public function restore(Request $request, int $ticketType): RedirectResponse
    {
        $this->catalog->restore($ticketType, $request->user());

        return back()->with('success', 'Ticket type restored as inactive.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct', 'exists:ticket_types,id']]);
        $this->catalog->reorder($data['ids'], $request->user());

        return back()->with('success', 'Ticket type order updated.');
    }
}
