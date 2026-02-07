<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::with(['systemOwner', 'province', 'city', 'creator']);

        $user = auth()->user();
        if (!$user->isSystemOwner()) {
            return $this->forbidden(__('messages.unauthorized_action'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_code', 'like', "%{$search}%")
                    ->orWhere('company_email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kyb_status')) {
            $query->where('kyb_status', $request->kyb_status);
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate(15);

        $clients = $clients->setCollection($clients->getCollection()->map(function ($item) {
            return new ClientResource($item);
        }));

        return $this->pagination(
            paginator: $clients,
            message: __('messages.clients_retrieved')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_code' => ['required', 'string', 'max:50', 'unique:clients,client_code'],
            'client_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:100'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'pic_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $user = auth()->user();

            if (!$user->isSystemOwner()) {
                return $this->forbidden(__('messages.unauthorized_action'));
            }

            $client = Client::create(array_merge($request->all(), [
                'system_owner_id' => $user->entity_id,
                'created_by' => $user->id,
                'kyb_status' => 'not_required',
                'status' => 'active',
            ]));

            return $this->created(
                data: new ClientResource($client->load(['systemOwner', 'province', 'city', 'creator'])),
                message: __('messages.client_created')
            );
        } catch (\Exception $e) {
            Log::error('Client creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.client_create_error')
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $client = Client::with(['systemOwner', 'province', 'city', 'creator'])->findOrFail($id);

            // Client users can only view their own client details
            if ($user->isClientUser()) {
                $userClientId = $user->getClientId();
                if ($client->id !== $userClientId) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }

            return $this->success(
                data: new ClientResource($client),
                message: __('messages.client_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.client_not_found'));
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'client_name' => ['sometimes', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:100'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'pic_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'status' => ['sometimes', 'in:active,inactive,suspended'],
        ]);

        try {
            $client = Client::findOrFail($id);
            $client->update($request->all());

            return $this->updated(
                data: new ClientResource($client->load(['systemOwner', 'province', 'city', 'creator'])),
                message: __('messages.client_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.client_not_found'));
        } catch (\Exception $e) {
            Log::error('Client update failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.client_update_error')
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);
            $client->status = $client->status === 'active' ? 'inactive' : 'active';
            $client->save();

            return $this->success(
                data: new ClientResource($client->load(['systemOwner', 'province', 'city', 'creator'])),
                message: __('messages.client_status_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.client_not_found'));
        }
    }
}
