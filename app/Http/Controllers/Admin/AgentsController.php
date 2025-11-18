<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Mail\UserGeneratedPasswordMail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AgentsController extends Controller
{
    const PAGINATE_PER_PAGE = 10;

    public function index(Request $request)
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $direction = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', self::PAGINATE_PER_PAGE);

        $query = User::agents();

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status'));
        }

        $query->sort($sortBy, $direction);

        $agents = $query->paginate($perPage)->appends($request->query());

        $options = [10, 20, 50];

        $view = $request->ajax()
            ? 'admin.agents-list.partials.agent_table'
            : 'admin.agents-list.index';

        return view($view, compact('agents', 'perPage', 'sortBy', 'direction', 'options'));
    }

    public function deactivateBulk(Request $request)
    {
        $agentIdsToDeactivate = json_decode($request->input('selected_agents', '[]'));

        $validated = $request->validate([
            'selected_agents' => 'required|json',
            'requested_tickets_action' => 'required|string|in:close,change_requester',
            'assigned_tickets_action' => 'required|string|in:unassign',
            'new_requester_id' => [
                'required_if:requested_tickets_action,change_requester',
                'exists:users,id',
                Rule::notIn($agentIdsToDeactivate),
            ],
        ], [
            'new_requester_id.not_in' => 'Вы не можете переназначить заявки деактивируемому пользователю.',
        ]);

        try {
            DB::transaction(function () use ($validated, $agentIdsToDeactivate) {
                $requestedAction = $validated['requested_tickets_action'];

                $ticketsCreatedQuery = Ticket::whereIn('user_id', $agentIdsToDeactivate)
                    ->where('status_id', TicketStatus::Open);

                if ($requestedAction === 'close') {
                    $ticketsCreatedQuery->update(['status_id' => TicketStatus::Closed]);
                } else {
                    $ticketsCreatedQuery->update(['user_id' => $validated['new_requester_id']]);
                }

                Ticket::whereIn('assigned_agent_id', $agentIdsToDeactivate)
                    ->where('status_id', TicketStatus::Open)
                    ->update(['assigned_agent_id' => null]);

                User::whereIn('id', $agentIdsToDeactivate)->update(['is_active' => false]);
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Произошла ошибка в ходе деактивации.');
        }

        return redirect()->back()->with('success', 'Выбранные агенты были успешно деактивированы.');
    }

    public function create(TimezoneService $timezoneService)
    {
        return view('admin.agents-list.create', [
            'timezones' => $timezoneService->getUniqueFormattedList(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'unique:users,email'],
            'login' => ['nullable', 'string', 'unique:users,login'],
            'last_name' => ['nullable', 'string'],
            'first_name' => ['required', 'string'],
            'patronymic' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'mobile_phone' => ['nullable', 'string'],
            'internal_number' => ['nullable', 'string'],
            'timezone' => ['required', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $password = $this->generatePassword();

        $data['password'] = bcrypt($password);

        $agent = User::create($data);

        Mail::to($agent->email)->send(new UserGeneratedPasswordMail($password));

        return redirect()->route('admin.agents.index');
    }

    private function generatePassword(): string
    {
        $letters = Str::upper(Str::random(4));
        $numbers = Str::random(2);
        $symbols = '!@#$%^&*()';
        $symbol = $symbols[random_int(0, strlen($symbols) - 1)];
        $symbol2 = $symbols[random_int(0, strlen($symbols) - 1)];

        return str_shuffle($letters.$numbers.$symbol.$symbol2);
    }

    public function edit(TimezoneService $timezoneService, User $agent)
    {
        return view('admin.agents-list.edit', [
            'timezones' => $timezoneService->getUniqueFormattedList(),
            'agent' => $agent,
        ]);
    }

    public function update(Request $request, User $agent)
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($agent->id)],
            'login' => ['nullable', 'string', Rule::unique('users', 'login')->ignore($agent->id)],
            'last_name' => ['nullable', 'string'],
            'first_name' => ['required', 'string'],
            'patronymic' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
            'mobile_phone' => ['nullable', 'string'],
            'internal_number' => ['nullable', 'string'],
            'timezone' => ['required', 'string'],
            'signature' => ['nullable', 'string'],
        ]);

        $agent->update($data);

        return redirect()->route('admin.agents.index');
    }
}
