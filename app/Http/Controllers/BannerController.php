<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->orderBy('id')->paginate(15);

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.form', ['banner' => new Banner]);
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new Banner);
    }

    public function update(Request $request, Banner $banner)
    {
        return $this->save($request, $banner);
    }

    private function save(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'image' => [$banner->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'link' => ['nullable', 'url:http,https', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'position' => ['nullable', 'in:home,1,2,3'],
            'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'boolean'],
        ]);
        unset($data['image']);
        $new = $request->hasFile('image') ? $request->file('image')->store('banners', 'public') : null;
        $old = $banner->image_path;
        try {
            $banner->fill($data + ($new ? ['image_path' => $new] : []))->save();
        } catch (\Throwable $e) {
            if ($new) {
                Storage::disk('public')->delete($new);
            } throw $e;
        }
        if ($new && $old) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('admin.banners.index')->with('success', 'Đã lưu banner.');
    }

    public function destroy(Banner $banner)
    {
        $path = $banner->image_path;
        $banner->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Đã xóa banner.');
    }

    public function toggleStatus(Request $request, Banner $banner)
    {
        $banner->update(['status' => $banner->status === 'active' ? 'inactive' : 'active']);

        return $request->expectsJson() ? response()->json(['status' => $banner->status]) : back()->with('success', 'Đã đổi trạng thái banner.');
    }
}
