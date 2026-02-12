<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeadQuarterResource;
use App\Models\HeadQuarter;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HeadQuarterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = HeadQuarter::with(['client', 'province', 'city', 'district', 'subDistrict']);

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

        $headQuarters = $query->orderBy('created_at', 'desc')->paginate(15);

        $headQuarters = $headQuarters->setCollection($headQuarters->getCollection()->map(function ($item) {
            return new HeadQuarterResource($item);
        }));

        return $this->pagination(
            paginator: $headQuarters,
            message: __('messages.head_quarters_retrieved')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validationRules = [
            'code' => ['required', 'string', 'max:50', 'unique:head_quarters,code'],
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

            $headQuarter = HeadQuarter::create(array_merge($request->all(), [
                'client_id' => $clientId,
                'status' => 'active',
            ]));

            return $this->created(
                data: new HeadQuarterResource($headQuarter->load(['client', 'province', 'city', 'district', 'subDistrict'])),
                message: __('messages.head_quarter_created')
            );
        } catch (\Exception $e) {
            Log::error('Head Quarter creation failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.head_quarter_create_error')
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $headQuarter = HeadQuarter::with(['client', 'province', 'city', 'district', 'subDistrict'])->findOrFail($id);

            // Authorization check based on user role
            if ($user->isHeadQuarterUser()) {
                // Head Quarter users can only view their own head quarter
                if ($headQuarter->id !== $user->getHeadQuarterId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            } elseif ($user->isClientUser()) {
                // Client users can only view head quarters under their client
                if ($headQuarter->client_id !== $user->getClientId()) {
                    return $this->forbidden(__('messages.unauthorized_action'));
                }
            }
            // System Owner can view all head quarters (no restriction)

            return $this->success(
                data: new HeadQuarterResource($headQuarter),
                message: __('messages.head_quarter_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.head_quarter_not_found'));
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
            $headQuarter = HeadQuarter::findOrFail($id);
            $headQuarter->update($request->all());

            return $this->updated(
                data: new HeadQuarterResource($headQuarter->load(['client', 'province', 'city', 'district', 'subDistrict'])),
                message: __('messages.head_quarter_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.head_quarter_not_found'));
        } catch (\Exception $e) {
            Log::error('Head Quarter update failed', [
                'error' => $e->getMessage()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('messages.head_quarter_update_error')
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $headQuarter = HeadQuarter::findOrFail($id);
            $headQuarter->status = $headQuarter->status === 'active' ? 'inactive' : 'active';
            $headQuarter->save();

            return $this->success(
                data: new HeadQuarterResource($headQuarter->load(['client', 'province', 'city', 'district', 'subDistrict'])),
                message: __('messages.head_quarter_status_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.head_quarter_not_found'));
        }
    }
}
