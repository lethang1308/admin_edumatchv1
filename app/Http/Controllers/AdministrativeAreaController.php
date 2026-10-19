<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeArea;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdministrativeAreaController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['level' => ['nullable', Rule::in(['province', 'district', 'ward'])], 'parent_id' => ['nullable', 'integer', 'exists:administrative_areas,id'], 'q' => ['nullable', 'string', 'max:100']]);
        $areas = AdministrativeArea::with('parent')->when($data['level'] ?? null, fn ($q, $v) => $q->where('level', $v))
            ->when($data['parent_id'] ?? null, fn ($q, $v) => $q->where('parent_id', $v))
            ->when($data['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))
            ->orderBy('level')->orderBy('name')->paginate(25)->withQueryString();
        $provinces = AdministrativeArea::where('level', 'province')->orderBy('name')->get();

        return view('admin.administrative_areas.index', compact('areas', 'provinces'));
    }
}
