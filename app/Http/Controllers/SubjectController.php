<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Rules\SafePublicImage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.mb_substr((string) $request->input('q'), 0, 150).'%'))
            ->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('admin.subjects.form', ['subject' => new Subject]);
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.form', compact('subject'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new Subject);
    }

    public function update(Request $request, Subject $subject)
    {
        return $this->save($request, $subject);
    }

    private function save(Request $request, Subject $subject)
    {
        $request->merge(['slug' => $request->input('slug') ?: Str::slug((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('subjects')->ignore($subject->id)],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('subjects')->ignore($subject->id)],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048', new SafePublicImage],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'], 'is_active' => ['required', 'boolean'],
        ]);
        unset($data['icon']);
        $old = $subject->icon_path;
        $new = $request->hasFile('icon') ? $request->file('icon')->store('subjects', 'public') : null;
        try {
            $subject->fill($data + ($new ? ['icon_path' => $new] : []))->save();
        } catch (\Throwable $e) {
            if ($new) {
                Storage::disk('public')->delete($new);
            } throw $e;
        }
        if ($new && $old) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('admin.subjects.index')->with('success', 'Đã lưu môn học.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->posts()->withTrashed()->exists() || $subject->teacherSubjects()->exists()
            || $subject->studentLearningNeeds()->exists() || $subject->searchLogs()->exists()) {
            throw ValidationException::withMessages(['subject' => 'Môn học đang được sử dụng. Hãy tắt hiển thị thay vì xóa.']);
        }
        $path = $subject->icon_path;
        try {
            $subject->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1451) {
                throw $e;
            } throw ValidationException::withMessages(['subject' => 'Môn học đang được sử dụng.']);
        }
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Đã xóa môn học.');
    }

    public function toggleStatus(Request $request, Subject $subject)
    {
        $subject->update(['is_active' => ! $subject->is_active]);

        return $request->expectsJson() ? response()->json(['is_active' => $subject->is_active]) : back()->with('success', 'Đã đổi trạng thái môn học.');
    }
}
