<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\System\InfrastructureConnectionIndexRequest;
use App\Http\Requests\Admin\System\StoreInfrastructureConnectionRequest;
use App\Http\Requests\Admin\System\UpdateInfrastructureConnectionRequest;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InfrastructureConnectionController extends Controller
{
    public function __construct(private readonly InfrastructureConnectionRegistry $registry, private readonly InfrastructureConnectionCatalogService $catalog) {}

    public function index(InfrastructureConnectionIndexRequest $request): Response
    {
        $q = InfrastructureConnection::query()->with('latestHealthCheck');
        $v = $request->validated();
        if (($v['archived'] ?? 'active') === 'archived') {
            $q->onlyTrashed();
        } elseif (($v['archived'] ?? 'active') === 'all') {
            $q->withTrashed();
        } foreach (['type', 'source'] as $f) {
            if (isset($v[$f])) {
                $q->where($f, $v[$f]);
            }
        } if (isset($v['search'])) {
            $q->where('name', 'like', '%'.$v['search'].'%');
        } if (isset($v['health'])) {
            $q->whereHas('latestHealthCheck', fn ($x) => $x->where('status', $v['health']));
        } $connections = $q->latest()->get()->map(fn ($c) => [...$this->catalog->safe($c), 'deleted_at' => $c->deleted_at?->toIso8601String(), 'latest_health_check' => $c->latestHealthCheck?->toArray()]);

        return Inertia::render('Admin/System/Connections/Index', ['connections' => $connections, 'definitions' => array_map(fn ($d) => $d->toArray(), $this->registry->definitions()), 'filters' => $v]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/System/Connections/Create', ['definitions' => array_map(fn ($d) => $d->toArray(), $this->registry->definitions())]);
    }

    public function store(StoreInfrastructureConnectionRequest $r): RedirectResponse
    {
        $this->catalog->create($r->validated(), $r->user());

        return to_route('admin.system.connections.index')->with('success', 'Connection created.');
    }

    public function edit(InfrastructureConnection $connection): Response
    {
        return Inertia::render('Admin/System/Connections/Edit', ['connection' => $this->catalog->safe($connection), 'definitions' => array_map(fn ($d) => $d->toArray(), $this->registry->definitions())]);
    }

    public function update(UpdateInfrastructureConnectionRequest $r, InfrastructureConnection $connection): RedirectResponse
    {
        $this->catalog->update($connection, $r->validated(), $r->user());

        return to_route('admin.system.connections.index')->with('success', 'Connection updated.');
    }

    public function toggle(Request $r, InfrastructureConnection $connection): RedirectResponse
    {
        $this->catalog->setEnabled($connection, ! $connection->is_enabled, $r->user());

        return back();
    }

    public function destroy(Request $r, InfrastructureConnection $connection): RedirectResponse
    {
        $this->catalog->archive($connection, $r->user());

        return back();
    }

    public function restore(Request $r, int $id): RedirectResponse
    {
        $this->catalog->restore($id, $r->user());

        return back();
    }

    public function forceDelete(Request $r, int $id): RedirectResponse
    {
        $this->catalog->forceDelete($id,$r->user());

        return back();
    }
}
