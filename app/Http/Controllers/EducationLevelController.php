<?php

namespace App\Http\Controllers;

use App\Models\EducationLevel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EducationLevelController extends Controller
{
    public function index(Request $request)
    {
        $levels = EducationLevel::when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.mb_substr((string) $request->input('q'), 0, 100).'%'))
            ->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.education_levels.index', compact('levels'));
    }

    public function create()
    {
        return view('admin.education_levels.form', ['level' => new EducationLevel]);
    }

    public function edit(EducationLevel $educationLevel)
    {
        return view('admin.education_levels.form', ['level' => $educationLevel]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new EducationLevel);
    }

    public function update(Request $request, EducationLevel $educationLevel)
    {
        return $this->save($request, $educationLevel);
    }

    private function save(Request $request, EducationLevel $level)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('education_levels')->ignore($level->id)],
            'name' => ['required', 'string', 'max:100'], 'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $level->fill($data)->save();

        return redirect()->route('admin.education-levels.index')->with('success', 'Đã lưu cấp lớp.');
    }

    public function destroy(EducationLevel $educationLevel)
    {
        if ($educationLevel->postLevels()->exists() || $educationLevel->studentLearningNeeds()->exists() || $educationLevel->searchLogs()->exists()) {
            throw ValidationException::withMessages(['level' => 'Cấp lớp đang được sử dụng. Hãy tắt hiển thị thay vì xóa.']);
        }
        try {
            $educationLevel->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1451) {
                throw $e;
            } throw ValidationException::withMessages(['level' => 'Cấp lớp đang được sử dụng.']);
        }

        return back()->with('success', 'Đã xóa cấp lớp.');
    }

    public function toggleStatus(Request $request, EducationLevel $educationLevel)
    {
        $educationLevel->update(['is_active' => ! $educationLevel->is_active]);

        return $request->expectsJson() ? response()->json(['is_active' => $educationLevel->is_active]) : back()->with('success', 'Đã đổi trạng thái cấp lớp.');
    }
}
