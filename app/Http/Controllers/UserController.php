<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdminUserRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Support\AccountRoles;
use App\Support\VietnamesePhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(AccountRoles::LABELS))],
            'status' => ['nullable', \Illuminate\Validation\Rule::in(['active', 'blocked'])]]);
        $users = User::with(['roles', 'studentProfile', 'teacherProfile', 'center'])
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereIn('phone', VietnamesePhone::variants($search))
                        ->orWhereHas('studentProfile', fn ($q) => $q->where('full_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('teacherProfile', fn ($q) => $q->where('full_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('center', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
                    if (ctype_digit($search)) {
                        $query->orWhere('users.id', (int) $search);
                    }
                });
            })
            ->when($filters['role'] ?? null, function ($query, $role) {
                if (in_array($role, ['super_admin', 'admin'], true)) {
                    $query->where(function ($query) use ($role) {
                        $query->where('role', $role)->orWhereHas('roles', fn ($q) => $q->where('name', $role));
                    });
                    if ($role === 'admin') {
                        $query->where('role', '!=', 'super_admin')->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'));
                    }
                } else {
                    $query->where('role', $role)->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']));
                }
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.user.index', ['users' => $users, 'roles' => AccountRoles::LABELS]);
    }

    public function destroy(User $user, AccountDeletionService $deletion)
    {
        if ($user->is(Auth::user())) {
            throw ValidationException::withMessages(['user' => 'Bạn không thể xóa tài khoản đang đăng nhập của chính mình.']);
        }
        $deletion->delete($user);

        return redirect()->route('admin.users.index')->with('success', 'Đã xóa tài khoản và thu hồi phiên đăng nhập. Email, số điện thoại có thể dùng để đăng ký tài khoản mới.');
    }

    public function create()
    {
        return view('admin.user.create', ['user' => new User, 'roles' => AccountRoles::LABELS]);
    }

    public function store(SaveAdminUserRequest $request)
    {
        $user = $this->saveAccount($request, new User);

        return redirect()->route('admin.users.show', $user)->with('success', 'Đã tạo tài khoản.');
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'studentProfile', 'teacherProfile', 'center']);

        return view('admin.user.edit', ['user' => $user, 'roles' => AccountRoles::LABELS]);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'studentProfile.province', 'studentProfile.district', 'studentProfile.ward',
            'teacherProfile.province', 'teacherProfile.district', 'teacherProfile.ward',
            'center.province', 'center.district', 'center.ward', 'center.branches',
            'subjects', 'teacherQualifications', 'submittedVerificationRequests.verificationDocuments', 'submittedVerificationRequests.reviewer']);

        return view('admin.user.show', ['user' => $user, 'roles' => AccountRoles::LABELS]);
    }

    public function editUser()
    {
        $user = Auth::user();

        return view('change_info', compact('user'));
    }

    public function update(SaveAdminUserRequest $request, User $user)
    {
        $this->saveAccount($request, $user);

        return redirect()->route('admin.users.show', $user)->with('success', 'Đã cập nhật tài khoản.');
    }

    private function saveAccount(SaveAdminUserRequest $request, User $user): User
    {
        $data = $request->validated();
        $previousRole = $user->exists ? AccountRoles::of($user) : null;
        $oldAvatar = $user->avatar_path;
        $newAvatar = $request->hasFile('avatar') ? $request->file('avatar')->store('avatars', 'public') : null;
        abort_if($newAvatar === false, 500, 'Không thể lưu ảnh đại diện.');
        try {
            DB::transaction(function () use ($data, $user, $newAvatar, $previousRole) {
                $values = \Illuminate\Support\Arr::only($data, ['name', 'email', 'phone', 'role', 'status']);
                $values['is_active'] = $data['status'] === 'active';
                $values['blocked_reason'] = $data['status'] === 'blocked' ? $data['blocked_reason'] : null;
                $values['blocked_at'] = $data['status'] === 'blocked' ? ($user->blocked_at ?? now()) : null;
                if (! empty($data['password'])) {
                    $values['password'] = $data['password'];
                }
                foreach (['email', 'phone'] as $contact) {
                    if (($data[$contact] ?? null) !== $user->{$contact}) {
                        $values[$contact.'_verified_at'] = null;
                    }
                }
                if ($newAvatar) {
                    $values['avatar_path'] = $newAvatar;
                }
                $user->fill($values)->save();
                $role = Role::findOrCreate($data['role'], 'web');
                if ($previousRole !== $data['role']) {
                    $user->syncRoles([$role]);
                } else {
                    // Preserve legacy admin aliases/custom groups when only editing profile data.
                    $user->assignRole($role);
                }
                $profile = \Illuminate\Support\Arr::only($data, ['bio', 'date_of_birth']);
                if ($data['role'] === 'student') {
                    $user->studentProfile()->updateOrCreate(['user_id' => $user->id], $profile + ['full_name' => $data['name']]);
                } elseif ($data['role'] === 'teacher') {
                    $user->teacherProfile()->updateOrCreate(['user_id' => $user->id], $profile
                        + \Illuminate\Support\Arr::only($data, ['headline', 'experience_years']) + ['full_name' => $data['name']]);
                } elseif ($data['role'] === 'center') {
                    $values = \Illuminate\Support\Arr::only($data, ['bio', 'founded_year', 'registered_address']);
                    $values += ['name' => $data['center_name'] ?? $data['name']];
                    if (! $user->center) {
                        $values['slug'] = (Str::slug($data['name']) ?: 'trung-tam').'-'.$user->id;
                        $values['contact_email'] = $data['email'] ?? null;
                        $values['hotline'] = $data['phone'] ?? null;
                    }
                    $user->center()->updateOrCreate(['owner_user_id' => $user->id], $values);
                }
                if ($data['status'] === 'blocked' || ! empty($data['password'])) {
                    $user->tokens()->delete();
                    $user->forceFill(['remember_token' => null])->save();
                }
            });
        } catch (\Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $exception;
        }
        if ($newAvatar && $oldAvatar && str_starts_with($oldAvatar, 'avatars/')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $user;
    }

    public function updateUser(Request $request)
    {
        // $user = Auth::user();
        $user = User::where('id', Auth::user()->id)->first();

        $validated = $request->validate([
            'name' => 'required|unique:users,name,'.$user->id,
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6',
            'phone' => 'required|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return redirect()->route('account.index')->with('success', 'Cập nhật người dùng thành công');
    }

    public function member(Request $request)
    {
        $query = $request->input('q'); // Lấy giá trị tìm kiếm từ URL ?q=xxx

        $users = User::query();

        if ($query) {
            $users->where(function ($q) use ($query) {
                $q->where('full_name', 'like', '%'.$query.'%')
                    ->orWhere('phone', 'like', '%'.$query.'%')
                    ->orWhere('id', $query); // Nếu nhập đúng ID thì tìm theo ID
            });
        }

        $users = $users->get(); // <<< thêm get() vào đây

        $members = $users->map(function ($user) {
            // Tính doanh số cá nhân
            $userSaleCompleted = Order::where('referrer_id', $user->id)
                ->where('status', 'completed') // Chỉ tính đơn đã hoàn thành
                ->sum('total');
            $userSale = Order::where('referrer_id', $user->id)
                ->sum('total');

            // Tính hoa hồng đã hoàn thành
            $commission_completed = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.referrer_id', $user->id)
                ->where('orders.status', 'completed')
                ->sum('order_items.commission_amount');

            return (object) [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->name,
                'phone' => $user->phone,
                'avatar' => $user->avatar ?? null,
                'joined_at' => $user->created_at,
                'personal_sales_completed' => $userSaleCompleted,
                'personal_sales' => $userSale,
                'commission' => $commission_completed,
                'total_members' => 0,
                'branch_count' => 0,
            ];
        });

        return view('member', compact('members', 'query')); // Truyền query ra view để hiện trong ô tìm kiếm
    }

    public function ambassador()
    {
        return view('ambassador'); // Truyền query ra view để hiện trong ô tìm kiếm

    }

    public function referrer(Request $request)
    {
        $referrerId = $request->query('ref');
        if (! Auth::check()) {
            return redirect()->route('signup', ['ref' => $referrerId]);
        }
        $user = Auth::user();
        $referrers = User::find($referrerId);
        session(['referrer_id' => $referrerId]);

        return view('referrer', compact('referrers', 'user'));
    }
}
