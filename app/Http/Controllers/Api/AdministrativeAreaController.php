<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeArea;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdministrativeAreaController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'level' => ['nullable', Rule::in(['province', 'district', 'ward'])],
            'parent_id' => ['nullable', 'integer', 'exists:administrative_areas,id'],
        ]);
        $query = AdministrativeArea::query()->where('is_active', true);
        if (! empty($data['level'])) {
            $query->where('level', $data['level']);
        }
        if (! empty($data['parent_id'])) {
            $query->where('parent_id', $data['parent_id']);
        } elseif (empty($data['level'])) {
            $query->whereNull('parent_id');
        }

        return response()->json(['data' => $query->orderBy('name')->get(['id', 'parent_id', 'code', 'name', 'level'])]);
    }
}
