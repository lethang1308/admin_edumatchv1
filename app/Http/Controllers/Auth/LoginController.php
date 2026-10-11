<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validate dữ liệu nhập vào
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'login.required' => 'Vui lòng nhập email hoặc số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // Xác định login là email hay số điện thoại
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Kiểm tra tài khoản có tồn tại không
        $user = User::where($loginType, $request->login)->first();

        if (!$user) {
            $errorMessage = $loginType === 'email' ? 'Email không tồn tại.' : 'Số điện thoại không tồn tại.';
            return redirect()->back()
                ->withErrors(['login' => $errorMessage])
                ->withInput($request->only('login'));
        }

        // Thử đăng nhập
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            return redirect('/admin')->with('success', 'Đăng nhập thành công!');
        }

        return redirect()->back()
            ->withErrors(['login' => 'Email/Số điện thoại hoặc mật khẩu không đúng.'])
            ->withInput($request->only('login')); // Giữ lại dữ liệu nhập trước đó
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('success', 'Bạn đã đăng xuất thành công!');
    }

    public function showSignupForm()
    {
        return view('signup');
    }

    public function signup(Request $request)
    {
        $referrerId = $request->input('ref');
        $AppSetting = AppSetting::first();
        $data = $request->validate([
            // 'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users|regex:/^0[0-9]{9}$/',
            // 'password'  => 'required|string|min:6|confirmed',
        ], [
            'phone.required' => 'Vui lòng nhập phone.',
            'phone.regex' => 'Nhập sai định dạng số điện thoại',
            'phone.unique' => 'Số điện thoại đã tồn tại.',
        ]);

        // Create new user
        $user = User::create([
            'full_name' => $data['phone'],
            'phone' => $data['phone'],
            'name' => $data['phone'],
            'balance' => $AppSetting->donated,
            'referrer_id' => $referrerId,
            'password' => Hash::make($data['phone']),
        ]);

        // Log the user in
        Auth::login($user);

        $soTien = number_format($AppSetting->donated, 0, '.', ',');
        return redirect()->route('home')->with('success', "Đăng ký thành công, bạn đã nhận được {$soTien}đ");

    }
}
