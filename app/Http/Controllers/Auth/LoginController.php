<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email address or password. / البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ]);
        }

        $request->session()->regenerate();
        return redirect()->route('home');
    }

    public function dashboard(Request $request)
    {
        $role = $request->user()->role;
        if (! in_array($role, ['student', 'instructor', 'manager'], true)) {
            abort(403);
        }
        return view('dashboard', ['user' => $request->user()]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
