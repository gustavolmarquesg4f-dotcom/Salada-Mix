<?php

namespace App\Domain\Identity;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SessionManager
{
    private function table(): string
    {
        if (config('session.driver') !== 'database') {
            abort(409, 'Gerenciamento de sessões requer SESSION_DRIVER=database.');
        }

        return config('session.table', 'sessions');
    }

    private function fingerprint(string $id): string
    {
        // Return only a stable opaque handle; the actual session identifier stays server-side.
        return hash_hmac('sha256', $id, config('app.key'));
    }

    public function read(Request $request): array
    {
        $rows = DB::table($this->table())->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')->limit(30)->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        return $rows->map(fn ($row) => [
            'fingerprint' => $this->fingerprint($row->id),
            'current' => hash_equals($row->id, $request->session()->getId()),
            'ip_address' => $row->ip_address,
            'device' => mb_substr((string) $row->user_agent, 0, 160),
            'last_active_at' => gmdate('c', (int) $row->last_activity),
        ])->all();
    }

    public function revoke(Request $request, string $fingerprint, ?string $currentPassword, bool $recentSso = false): bool
    {
        $user = $request->user();
        $passwordAuthorized = $user->password_login_enabled
            && $currentPassword
            && Hash::check($currentPassword, $user->password);

        if (! $passwordAuthorized && ! $recentSso) {
            throw ValidationException::withMessages([
                'current_password' => 'Confirme sua senha ou autentique-se novamente pelo SSO.',
            ]);
        }

        $rows = DB::table($this->table())->where('user_id', $request->user()->id)
            ->get(['id']);

        foreach ($rows as $row) {
            if (! hash_equals($this->fingerprint($row->id), $fingerprint)) {
                continue;
            }

            if (hash_equals($row->id, $request->session()->getId())) {
                throw ValidationException::withMessages(['session' => 'Utilize Sair para encerrar a sessão atual.']);
            }

            return DB::table($this->table())->where('user_id', $request->user()->id)
                ->where('id', $row->id)->delete() === 1;
        }

        abort(404);
    }

    public function revokeOtherSessions(User $user, string $currentSessionId): void
    {
        if (config('session.driver') === 'database') {
            DB::table($this->table())->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)->delete();
        }
    }
}
