<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly PortalAuthService $portal) {}

    public function create(Request $request): View|RedirectResponse
    {
        $token = $request->query('token');

        if ($token) {
            return $this->handleTokenCallback($request, (string) $token);
        }

        /** @var view-string $view */
        $view = 'auth.login';

        return view($view);
    }

    /**
     * Handle Portal callback with GameToken after registration or login.
     */
    private function handleTokenCallback(Request $request, string $token): RedirectResponse
    {
        $result = $this->portal->consumeToken($token);

        if ($result === null) {
            return redirect()->route('login')
                ->withErrors(['username' => 'Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.']);
        }

        $user = User::syncFromPortal($result, (string) $request->ip());

        Auth::login($user);

        $request->session()->regenerate();

        $tokenData = $this->portal->issueToken($result['uid']);
        if ($tokenData !== null) {
            session([
                'portal_game_token' => $tokenData['token'],
                'portal_coin_balance' => $tokenData['wallet_coin'],
                'portal_token_expires' => $tokenData['expires_at'],
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! config('muh5.server_open')) {
            $allowed = (array) config('muh5.allowed_usernames', []);
            if (! in_array($request->username, $allowed, true)) {
                return back()
                    ->withInput($request->only('username'))
                    ->withErrors(['username' => 'Máy chủ chưa mở, vui lòng quay lại sau!']);
            }
        }

        $result = $this->portal->login((string) $request->username, (string) $request->password);

        if ($result === null) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Tên đăng nhập hoặc mật khẩu không đúng.']);
        }

        $user = User::syncFromPortal($result, (string) $request->ip());

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        // Lấy session GameToken từ Portal để dùng cho wallet operations
        $tokenData = $this->portal->issueToken($result['uid']);
        if ($tokenData !== null) {
            session([
                'portal_game_token' => $tokenData['token'],
                'portal_coin_balance' => $tokenData['wallet_coin'],
                'portal_token_expires' => $tokenData['expires_at'],
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
