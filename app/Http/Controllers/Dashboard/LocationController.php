<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\SubDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        $query = Province::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $provinces = $query->orderBy('name')->get();

        return $this->success(
            data: $provinces,
            message: __('messages.provinces_retrieved')
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $query = City::with('province');

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $cities = $query->orderBy('name')->get();

        return $this->success(
            data: $cities,
            message: __('messages.cities_retrieved')
        );
    }

    public function districts(Request $request): JsonResponse
    {
        $query = District::with('city');

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $districts = $query->orderBy('name')->get();

        return $this->success(
            data: $districts,
            message: __('messages.districts_retrieved')
        );
    }

    public function subDistricts(Request $request): JsonResponse
    {
        $query = SubDistrict::with('district');

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $subDistricts = $query->orderBy('name')->get();

        return $this->success(
            data: $subDistricts,
            message: __('messages.sub_districts_retrieved')
        );
    }
}
