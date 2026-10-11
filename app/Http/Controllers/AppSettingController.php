<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Http\Requests\StoreAppSettingRequest;
use App\Http\Requests\UpdateAppSettingRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AppSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = AppSetting::first(); // Lấy thông tin cài đặt
        if (!$setting) {
            $setting = new AppSetting();
        }
        return view('admin.app_setting.edit', compact('setting'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $request;
        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'default_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{3}){1,2}$/',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|image|max:2048',
            'banner_path' => 'nullable|image|max:4096',
            'favicon_path' => 'nullable|image|max:1024',
            'donated' => 'nullable',
        ]);

        $setting = AppSetting::first() ?? new AppSetting();

        $setting->fill($validated);

        // Xử lý upload file nếu có
        if ($request->hasFile('logo_path')) {
            $setting->logo_path = $request->file('logo_path')->store('uploads/logo', 'public');
        }
        if ($request->hasFile('banner_path')) {
            $setting->banner_path = $request->file('banner_path')->store('uploads/banner', 'public');
        }
        if ($request->hasFile('favicon_path')) {
            $setting->favicon_path = $request->file('favicon_path')->store('uploads/favicon', 'public');
        }

        $setting->save();

        return redirect()->back()->with('success', 'Cập nhật thông tin ứng dụng thành công!');
    }


    /**
     * Display the specified resource.
     */
    public function show(AppSetting $appSetting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppSetting $appSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // return $request;
        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'default_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{3}){1,2}$/',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|image|max:2048',
            'banner_path' => 'nullable|image|max:4096',
            'favicon_path' => 'nullable|image|max:1024',
            'donated' => 'nullable',
        ]);

        $setting = AppSetting::first() ?? new AppSetting();

        $setting->fill($validated);

        // Xử lý xóa ảnh nếu có yêu cầu
        if ($request->has('delete_logo') && $setting->logo_path) {
            if (Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $setting->logo_path = null;
        }

        if ($request->has('delete_favicon') && $setting->favicon_path) {
            if (Storage::disk('public')->exists($setting->favicon_path)) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
            $setting->favicon_path = null;
        }

        if ($request->has('delete_banner') && $setting->banner_path) {
            if (Storage::disk('public')->exists($setting->banner_path)) {
                Storage::disk('public')->delete($setting->banner_path);
            }
            $setting->banner_path = null;
        }

        // Xử lý upload file nếu có
        if ($request->hasFile('logo_path')) {
            // Xóa ảnh cũ nếu upload mới
            if ($setting->logo_path && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $setting->logo_path = $request->file('logo_path')->store('uploads/logo', 'public');
        }
        if ($request->hasFile('banner_path')) {
            // Xóa ảnh cũ nếu upload mới
            if ($setting->banner_path && Storage::disk('public')->exists($setting->banner_path)) {
                Storage::disk('public')->delete($setting->banner_path);
            }
            $setting->banner_path = $request->file('banner_path')->store('uploads/banner', 'public');
        }
        if ($request->hasFile('favicon_path')) {
            // Xóa ảnh cũ nếu upload mới
            if ($setting->favicon_path && Storage::disk('public')->exists($setting->favicon_path)) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
            $setting->favicon_path = $request->file('favicon_path')->store('uploads/favicon', 'public');
        }

        $setting->save();

        return redirect()->back()->with('success', 'Cập nhật thông tin ứng dụng thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppSetting $appSetting)
    {
        //
    }


    public function openApp()
    {
        $totalOrders = 0;

        // Tính tổng doanh thu từ cột `total`
        $totalRevenue = 0;

        $totalUsers = User::count();
        $todayRevenue = 0;

        // Lấy tổng số sản phẩm
        $totalSuccessfulOrders = 0;

        $days = 7; // 7 ngày gần nhất

        $revenueData = [];

        // Tạo mảng dữ liệu: labels (ngày), values (doanh thu)
        $labels = [];
        $values = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $labels[] = $date;

            $dayData = $date;
            $values[] = [];
        }

        $orders = [];
        return view('admin.index', compact(
            'totalOrders',
            'totalRevenue',
            'totalUsers',
            'totalSuccessfulOrders',
            'labels',
            'values',
            'todayRevenue',
            'orders'
        ));
    }
}
