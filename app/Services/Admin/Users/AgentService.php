<?php

namespace App\Services\Admin\Users;

use App\Enums\TicketStatus;
use App\Mail\UserGeneratedPasswordMail;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Admin\Users\AgentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AgentService
{
    public function __construct(
        private AgentRepository $repository
    ) {}

    public function list(array $filters, string $sortBy, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = $this->repository->query();

        $query = $this->repository->filter($query, $filters);
        $query = $this->repository->sort($query, $sortBy, $direction);

        return $this->repository->paginate($query, $perPage);
    }

    public function deactivateBulk(array $agentIds, string $requestedTicketsAction, ?int $newRequesterId = null): void
    {
        DB::transaction(function () use ($agentIds, $requestedTicketsAction, $newRequesterId) {

            $ticketsQuery = Ticket::whereIn('user_id', $agentIds)
                ->where('status_id', TicketStatus::Open);

            if ($requestedTicketsAction === 'close') {
                $ticketsQuery->update(['status_id' => TicketStatus::Closed]);
            } else {
                $ticketsQuery->update(['user_id' => $newRequesterId]);
            }

            Ticket::whereIn('assigned_agent_id', $agentIds)
                ->where('status_id', TicketStatus::Open)
                ->update(['assigned_agent_id' => null]);

            User::whereIn('id', $agentIds)
                ->update(['is_active' => false]);
        });
    }

    public function createAgent(array $data): User
    {
        $password = $this->generatePassword();
        $data['password'] = bcrypt($password);

        $agent = User::create($data);

        Mail::to($agent->email)->send(new UserGeneratedPasswordMail($password));

        return $agent;
    }

    private function generatePassword(): string
    {
        $letters = Str::upper(Str::random(4));
        $numbers = Str::random(2);
        $symbols = '!@#$%^&*()';
        $symbol1 = $symbols[random_int(0, strlen($symbols) - 1)];
        $symbol2 = $symbols[random_int(0, strlen($symbols) - 1)];

        return str_shuffle($letters.$numbers.$symbol1.$symbol2);
    }

    public function updateAgent(User $agent, array $data): User
    {
        $agent->update($data);

        return $agent;
    }

    public function tableHead(): array
    {
        return [
            [
                'type' => 'checkbox',
            ],
            [
                'title' => __('lang.agents_list.full_name'),
                'column' => 'last_name',
                'sortable' => true,
            ],
            [
                'title' => __('lang.agents_list.login'),
                'column' => 'login',
                'sortable' => true,
            ],
            [
                'title' => __('lang.agents_list.email'),
                'column' => 'email',
                'sortable' => true,
            ],
            [
                'title' => __('lang.agents_list.phone'),
            ],
            [
                'title' => __('lang.agents_list.account_info'),
            ],
            [
                'title' => __('lang.agents_list.actions'),
            ],
        ];
    }
}
