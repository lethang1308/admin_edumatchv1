<?php

namespace App\Support;

use App\Models\User;

class AccountRoles
{
    public const LABELS = [
        'student' => 'Học viên',
        'teacher' => 'Giáo viên',
        'center' => 'Trung tâm',
        'admin' => 'Quản trị viên',
        'super_admin' => 'Super Admin',
    ];

    public static function of(User $user): string
    {
        // Legacy administrators may still have role=student in users.
        foreach (['super_admin', 'admin'] as $role) {
            if ($user->role === $role || $user->hasRole($role)) {
                return $role;
            }
        }

        return $user->role;
    }
}
