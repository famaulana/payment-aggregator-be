<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only System Owner, Client, and Head Quarter can list merchants
        if (!$user->isSystemOwner() && !$user->isClientUser() && !$user->isHeadQuarterUser()) {
            return $this->forbidden(__('messages.unauthorized_action'));
        }

        $query = Merchant::with(['client', 'headQuarter', 'province', 'city', 'district', 'subDistrict', 'creator']);

        if ($user->isClientUser()) {
            $query->where('client_id', $user->getClientId());
        } elseif ($user->isHeadQuarterUser()) {
            $query->where('head_quarter_id', $user->getHeadQuarterId());
        }
        // System owner can see all merchants (no filter needed)

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('head_quarter_id')) {
            $query->where('head_quarter_id', $request->head_quarter_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('merchant_name', 'like', "%{$search}%")
                    ->orWhere('merchant_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $merchants = $query->orderBy('created_at', 'desc')->paginate(15);

        $merchants = $merchants->setCollection($merchants->getCollection()->map(function ($item) {
            return new MerchantResource($item);
        }));

        return $this->pagination(
            paginator: $merchants,
            message: __('messages.merchants_retrieved')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validationRules = [
            'merchant_code' => ['required', 'string', 'max:50', 'unique:merchants,merchant_code'],
            'merchant_name' => ['required', 'string', 'max:255'],
            'head_quarter_id' => ['nullable', 'exists:head_quarters,id'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'pos_merchant_id' => ['nullable', 'string', 'max:100'],
        ];

        // System Owner must provide client_id
        if ($user->isSystemOwner()) {
            $validationRules['client_id'] = ['required', 'exists:clients,id'];
        }

        $request->validate($validationRules);

        try {
            if (!$user->isSystemOwner() && !$user->isClientUser() && !$user->isHeadQuarterUser()) {
                return $this->forbidden(__('messages.unauthorized_action'));
            }

            $clientId = $user->isSystemOwner() && $request->has('client_id')
                ? $request->client_id
                : $user->getClientId();

            $merchant = Merchant::create(array_merge($request->all(), [
                'client_id' => $clientId,
                'created_by' => $user->id,
                'status' => 'active',
            ]));

            return $this->created(
                data: new MerchantResource($merchant->load(['client', 'headQuarter', 'province', 'city', 'district', 'subDistrict', 'creator'])),
                message: __('messages.merchant_created')
            );
        } catch (\Exception $e) {
            Log::error('Merchant creation failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.merchant_create_error')
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $merchant = Merchant::with(['client', 'headQuarter', 'province', 'city', 'district', 'subDistrict', 'creator'])->findOrFail($id);

            // Authorization check based on user role
            if ($user->isMerchantUser()) {
                // Merchant users can only view their own merchant
                if ($merchant->id !== $user->getMerchantId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            } elseif ($user->isHeadQuarterUser()) {
                // Head Quarter users can only view merchants under their head quarter
                if ($merchant->head_quarter_id !== $user->getHeadQuarterId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            } elseif ($user->isClientUser()) {
                // Client users can only view merchants under their client
                if ($merchant->client_id !== $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }
            // System Owner can view all merchants (no restriction)

            return $this->success(
                data: new MerchantResource($merchant),
                message: __('messages.merchant_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.merchant_not_found'));
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'merchant_name' => ['sometimes', 'string', 'max:255'],
            'head_quarter_id' => ['nullable', 'exists:head_quarters,id'],
            'province_id' => ['sometimes', 'exists:provinces,id'],
            'city_id' => ['sometimes', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'pos_merchant_id' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        try {
            $merchant = Merchant::findOrFail($id);
            $merchant->update($request->all());

            return $this->updated(
                data: new MerchantResource($merchant->load(['client', 'headQuarter', 'province', 'city', 'district', 'subDistrict', 'creator'])),
                message: __('messages.merchant_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.merchant_not_found'));
        } catch (\Exception $e) {
            Log::error('Merchant update failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.merchant_update_error')
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $merchant = Merchant::findOrFail($id);
            $merchant->status = $merchant->status === 'active' ? 'inactive' : 'active';
            $merchant->save();

            return $this->success(
                data: new MerchantResource($merchant->load(['client', 'headQuarter', 'province', 'city', 'district', 'subDistrict', 'creator'])),
                message: __('messages.merchant_status_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.merchant_not_found'));
        }
    }
}
