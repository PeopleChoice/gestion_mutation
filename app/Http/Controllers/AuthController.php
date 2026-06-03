<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            ActivityLog::log('connexion', 'auth', 'Connexion réussie', ['email' => $request->email]);
            return redirect()->intended('/dashboard');
        }

        ActivityLog::create([
            'action' => 'connexion_echouee',
            'module' => 'auth',
            'description' => "Tentative de connexion échouée pour {$request->email}",
            'ip_address' => $request->ip(),
            'details' => ['email' => $request->email],
        ]);

        return back()->withErrors([
            'email' => 'Les identifiants sont incorrects.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        ActivityLog::log('deconnexion', 'auth', 'Déconnexion');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
