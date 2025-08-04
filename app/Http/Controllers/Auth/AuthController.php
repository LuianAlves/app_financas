<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LogoutResponse;

use App\Models\Auth\User;

use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $guard;
    protected $user;

    public function __construct(StatefulGuard $guard, User $user)
    {
        $this->guard = $guard;
        $this->user = $user;
    }

    public function login(LoginUserRequest $request)
    {
        $request->validated();

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

//            dd('teste 1');
            if ($user->canAuthenticate()) {

//                dd('teste 2');
                if ($request->hasSession()) {
//                    dd('teste 3');
                    $request->session()->regenerateToken();
                }

//                dd('teste 4');
                return redirect()->route('dashboard');
            }

            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return back()->with('error', 'Acesso negado.');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas estão incorretas.',
        ]);
    }

    public function register(RegisterUserRequest $request)
    {
        $request->validated();

        $user = $this->user->create([
            'name' => ucwords($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => 1,
            'created_at' => Carbon::now()
        ])->assignRole('user');

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            /** @var \App\Models\User $user */
            if ($user->canAuthenticate()) {

                if ($request->hasSession()) {
                    $request->session()->regenerateToken();
                }

                return redirect()->intended('/dashboard');
            } else {
                Auth::logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return back()->with('error', 'Acesso negado.');
            }
        }

        // Se falhar a autenticação, retorna um erro
        return back()->withErrors([
            'email' => 'As credenciais fornecidas estão incorretas.',
        ]);
    }

    public function destroy(Request $request): LogoutResponse
    {
        $this->guard->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return app(LogoutResponse::class);
    }

    public function welcome()
    {
        return view('auth.login');
    }

    public function registerView()
    {
        return view('auth.register');
    }
}
