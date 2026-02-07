<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeadOfficeResource;
use App\Models\HeadOffice;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HeadOfficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = HeadOffice::with(['client', 'province', 'city', 'district', 'subDistrict']);

        if ($user->isClientUser()) {
            $query->where('client_id', $user->getClientId());
        } elseif (!$user->isSystemOwner()) {
            return $this->forbidden(__('messages.unauthorized_action'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $headOffices = $query->orderBy('created_at', 'desc')->paginate(15);

        $headOffices = $headOffices->setCollection($headOffices->getCollection()->map(function ($item) {
            return new HeadOfficeResource($item);
        }));

        return $this->pagination(
            paginator: $headOffices,
            message: __('messages.head_offices_retrieved')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validationRules = [
            'code' => ['required', 'string', 'max:50', 'unique:head_offices,code'],
            'name' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ];

        // System Owner must provide client_id
        if ($user->isSystemOwner()) {
            $validationRules['client_id'] = ['required', 'exists:clients,id'];
        }

        $request->validate($validationRules);

        try {
            if (!$user->isSystemOwner() && !$user->isClientUser()) {
                return $this->forbidden(__('messages.unauthorized_action'));
            }

            $clientId = $user->isSystemOwner() && $request->has('client_id')
                ? $request->client_id
                : $user->getClientId();

            $headOffice = HeadOffice::create(array_merge($request->all(), [
                'client_id' => $clientId,
                'status' => 'active',
            ]));

            return $this->created(
                data: new HeadOfficeResource($headOffice->load(['client', 'province', 'city', 'district', 'subDistrict'])),
                message: __('messages.head_office_created')
            );
        } catch (\Exception $e) {
            Log::error('Head Office creation failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.head_office_create_error')
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $headOffice = HeadOffice::with(['client', 'province', 'city', 'district', 'subDistrict'])->findOrFail($id);

            // Authorization check based on user role
            if ($user->isHeadOfficeUser()) {
                // Head Office users can only view their own head office
                if ($headOffice->id !== $user->getHeadOfficeId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            } elseif ($user->isClientUser()) {
                // Client users can only view head offices under their client
                if ($headOffice->client_id !== $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }
            // System Owner can view all head offices (no restriction)

            return $this->success(
                data: new HeadOfficeResource($headOffice),
                message: __('messages.head_office_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.head_office_not_found'));
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'province_id' => ['sometimes', 'exists:provinces,id'],
            'city_id' => ['sometimes', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        try {
            $headOffice = HeadOffice::findOrFail($id);
            $headOffice->update($request->all());

            return $this->updated(
                data: new HeadOfficeResource($headOffice->load(['client', 'province', 'city', 'district', 'subDistrict'])),
                message: __('messages.head_office_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.head_office_not_found'));
        } catch (\Exception $e) {
            Log::error('Head Office update failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.head_office_update_error')
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $headOffice = HeadOffice::findOrFail($id);
            $headOffice->status = $headOffice->status === 'active' ? 'inactive' : 'active';
            $headOffice->save();

            return $this->success(
                data: new HeadOfficeResource($headOffice->load(['client', 'province', 'city', 'district', 'subDistrict'])),
                message: __('messages.head_office_status_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.head_office_not_found'));
        }
    }
}
