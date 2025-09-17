<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash; // Menggunakan Hash untuk memverifikasi password
use Illuminate\Support\Facades\DB; // Menggunakan DB Query Builder
use App\Models\Pengguna;
use Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'name';
    }

    public function login(Request $request)
    {
        // Validasi input login (username dan password)
        $this->validateLogin($request);

        // Cek apakah ada terlalu banyak percobaan login
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // Ambil data dari input
        // $credentials = $request->only($this->username(), 'password');

        // Gunakan DB untuk mengambil data pengguna berdasarkan username LOGIN
        $user = Pengguna::where('LOGIN', $request->name)->first();
        // $user = DB::table('aplikasi.pengguna')->where('LOGIN', $credentials[$this->username()])->first();

        // Verifikasi password
        if ($user && Hash::check($request->password, $user->PASSWORD)) {
            // Jika password cocok, login pengguna
            Auth::login($user);

            // Auth::loginUsingId($user->id); // Atau Auth::login($user) jika menggunakan Eloquent model
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());

                // Tambahkan di sini untuk cek session
                // $request->session()->put('test', 'halo');
                // dd(session()->all());
            }

            // Kirimkan respons login sukses
            // dd($user);
            // return redirect()->intended('/dashboard');
            return $this->sendLoginResponse($request);
        }

        // Jika login gagal, tingkatkan percobaan login
        $this->incrementLoginAttempts($request);

        // Kirimkan respons login gagal
        return $this->sendFailedLoginResponse($request);
    }

    public function logout(Request $request)
    {
        // $this->guard()->logout();

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        // if ($response = $this->loggedOut($request)) {
        //     return $response;
        // }

        // return $request->wantsJson()
        //     ? new JsonResponse([], 204)
        //     : redirect('/login');

        Auth::logout();  // Logout pengguna

        $request->session()->invalidate();  // Menghapus session

        $request->session()->regenerateToken();  // Regenerasi token CSRF untuk keamanan

        return redirect('/login');  // Redirect ke halaman login setelah logout
    }
}
