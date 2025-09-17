<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;

class BasicAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (!$username || !$password) {
            return response()->json(['message' => 'Unauthorized'], 401, [
                'WWW-Authenticate' => 'Basic',
            ]);
        }

        $user = Pengguna::where('LOGIN', $username)->first();

        if ($user && Hash::check($password, $user->PASSWORD)) {
            // kalau perlu, bisa Auth::login($user);
            return $next($request);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
