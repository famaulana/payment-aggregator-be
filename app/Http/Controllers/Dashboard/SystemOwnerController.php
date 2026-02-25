<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\SystemOwnerResource;
use App\Models\SystemOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemOwnerController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /dashboard/system-owners
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = SystemOwner::with(['province', 'city']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('company_email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $systemOwners = $query->orderByDesc('created_at')->paginate(15);

        $systemOwners->setCollection(
            $systemOwners->getCollection()->map(fn($item) => new SystemOwnerResource($item))
        );

        return $this->pagination($systemOwners, __('messages.system_owners_retrieved'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /dashboard/system-owners/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $systemOwner = SystemOwner::with(['province', 'city'])->find($id);

        if (!$systemOwner) {
            return $this->notFound(__('messages.system_owner_not_found'));
        }

        return $this->success(
            new SystemOwnerResource($systemOwner),
            __('messages.system_owner_retrieved')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /dashboard/system-owners
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'          => ['required', 'string', 'max:50', 'unique:system_owners,code'],
            'name'          => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'pic_name'      => ['nullable', 'string', 'max:255'],
            'pic_position'  => ['nullable', 'string', 'max:100'],
            'pic_phone'     => ['nullable', 'string', 'max:20'],
            'pic_email'     => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'province_id'   => ['nullable', 'exists:provinces,id'],
            'city_id'       => ['nullable', 'exists:cities,id'],
            'address'       => ['nullable', 'string'],
            'postal_code'   => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $systemOwner = SystemOwner::create(array_merge(
                $request->only([
                    'code', 'name', 'business_type',
                    'pic_name', 'pic_position', 'pic_phone', 'pic_email',
                    'company_phone', 'company_email',
                    'province_id', 'city_id', 'address', 'postal_code',
                ]),
                ['status' => 'active']
            ));

            return $this->created(
                new SystemOwnerResource($systemOwner->load(['province', 'city'])),
                __('messages.system_owner_created')
            );
        } catch (\Exception $e) {
            Log::error('SystemOwner creation failed', ['error' => $e->getMessage()]);
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, __('messages.system_owner_create_error'));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /dashboard/system-owners/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'pic_name'      => ['nullable', 'string', 'max:255'],
            'pic_position'  => ['nullable', 'string', 'max:100'],
            'pic_phone'     => ['nullable', 'string', 'max:20'],
            'pic_email'     => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'province_id'   => ['nullable', 'exists:provinces,id'],
            'city_id'       => ['nullable', 'exists:cities,id'],
            'address'       => ['nullable', 'string'],
            'postal_code'   => ['nullable', 'string', 'max:10'],
        ]);

        $currentUser = auth()->user();

        // Sub-roles (non system_owner) can only update their own entity
        if (!$currentUser->hasRole('system_owner') && $currentUser->entity_id !== $id) {
            return $this->forbidden(__('messages.forbidden'));
        }

        $systemOwner = SystemOwner::find($id);

        if (!$systemOwner) {
            return $this->notFound(__('messages.system_owner_not_found'));
        }

        try {
            $systemOwner->update($request->only([
                'name', 'business_type',
                'pic_name', 'pic_position', 'pic_phone', 'pic_email',
                'company_phone', 'company_email',
                'province_id', 'city_id', 'address', 'postal_code',
            ]));

            return $this->success(
                new SystemOwnerResource($systemOwner->load(['province', 'city'])),
                __('messages.system_owner_updated')
            );
        } catch (\Exception $e) {
            Log::error('SystemOwner update failed', ['error' => $e->getMessage()]);
            return $this->error(ResponseCode::INTERNAL_SERVER_ERROR, __('messages.system_owner_update_error'));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /dashboard/system-owners/{id}/toggle-status
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(int $id): JsonResponse
    {
        $systemOwner = SystemOwner::find($id);

        if (!$systemOwner) {
            return $this->notFound(__('messages.system_owner_not_found'));
        }

        $systemOwner->status = $systemOwner->status === 'active' ? 'inactive' : 'active';
        $systemOwner->save();

        return $this->success(
            new SystemOwnerResource($systemOwner->load(['province', 'city'])),
            __('messages.system_owner_status_updated')
        );
    }
}
