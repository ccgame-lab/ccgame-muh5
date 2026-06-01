<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GreenJadeAuthController extends Controller
{
    public function loginBridge(Request $request): RedirectResponse
    {
        return redirect()->route('auth.greenjade.login.perform');
    }

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(128);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $request->session()->put('greenjade_state', $state);
        $request->session()->put('greenjade_code_verifier', $codeVerifier);

        $query = http_build_query([
            'client_id' => config('services.greenjade_id.client_id'),
            'redirect_uri' => config('services.greenjade_id.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect((string) config('services.greenjade_id.base_url').'/oauth/authorize?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->query('state');
        $code = $request->query('code');

        if (! $state || ! $code || $state !== $request->session()->get('greenjade_state')) {
            return redirect('/')->withErrors(['error' => 'Invalid state. Please try again.']);
        }

        $codeVerifier = $request->session()->pull('greenjade_code_verifier');
        $request->session()->pull('greenjade_state');

        // Exchange code for token
        $response = Http::asForm()->post((string) config('services.greenjade_id.base_url').'/api/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.greenjade_id.client_id'),
            'client_secret' => config('services.greenjade_id.client_secret'),
            'redirect_uri' => config('services.greenjade_id.redirect_uri'),
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ]);

        if ($response->failed()) {
            return redirect('/')->withErrors(['error' => 'Failed to obtain access token.']);
        }

        $accessToken = (string) $response->json('access_token');

        // Fetch user info
        $userResponse = Http::withToken($accessToken)->get((string) config('services.greenjade_id.base_url').'/api/oauth/userinfo');

        if ($userResponse->failed()) {
            return redirect('/')->withErrors(['error' => 'Failed to retrieve user information.']);
        }

        $userInfo = $userResponse->json();

        if (empty($userInfo['sub']) || empty($userInfo['username'])) {
            return redirect('/')->withErrors(['error' => 'Invalid user info returned.']);
        }

        // Try to match by new sub, or fallback to username to migrate old ULID portal_uid
        $user = User::where('portal_uid', $userInfo['sub'])
            ->orWhere('username', $userInfo['username'])
            ->first();

        if ($user) {
            $user->update([
                'portal_uid' => $userInfo['sub'],
                'name' => $userInfo['name'] ?? $userInfo['username'],
                'email' => $userInfo['email'] ?? $user->email,
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
            ]);
        } else {
            $user = User::create([
                'portal_uid' => $userInfo['sub'],
                'username' => $userInfo['username'],
                'name' => $userInfo['name'] ?? $userInfo['username'],
                'email' => $userInfo['email'] ?? null,
                'password' => 'greenjade-sso', // Marker
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/play');
    }

    /**
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public function logout(Request $request): mixed
    {
        return response()->view('auth.logout-loading', [
            'nextUrl' => route('auth.greenjade.logout.perform'),
        ]);
    }

    public function performLogout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $baseUrl = rtrim(
            (string) config('services.greenjade_id.base_url', env('GREENJADE_ID_BASE_URL', 'https://id.greenjade.net')),
            '/'
        );
        $redirectUri = url('/');

        return redirect()->away(
            $baseUrl.'/oauth/logout?redirect_uri='.urlencode($redirectUri)
        );
    }
}
