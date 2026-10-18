<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStudentProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private const RELATIONS = [
        'studentProfile.province', 'studentProfile.district', 'studentProfile.ward',
        'teacherProfile.province', 'teacherProfile.district', 'teacherProfile.ward',
        'center.province', 'center.district', 'center.ward',
    ];

    public function show(Request $request)
    {
        return response()->json(['user' => $request->user()->load(self::RELATIONS)]);
    }

    public function update(UpdateStudentProfileRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        DB::transaction(function () use ($user, $data) {
            if (array_key_exists('name', $data)) {
                $user->update(['name' => $data['name']]);
            }
            unset($data['name']);
            $user->studentProfile()->updateOrCreate(['user_id' => $user->id], $data + ['full_name' => $user->name]);
        });

        return response()->json(['message' => 'Đã cập nhật hồ sơ cá nhân.', 'user' => $user->fresh()->load(self::RELATIONS)]);
    }

    public function avatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048']]);
        $path = $request->file('avatar')->store('avatars', 'public');
        abort_unless($path, 500, 'Không thể lưu ảnh đại diện.');
        $user = $request->user();
        $oldPath = $user->avatar_path;
        try {
            $user->update(['avatar_path' => $path]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        if (is_string($oldPath) && str_starts_with($oldPath, 'avatars/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Đã cập nhật ảnh đại diện.', 'avatar_url' => $user->avatar_url,
            'user' => $user->load(self::RELATIONS),
        ]);
    }
}
