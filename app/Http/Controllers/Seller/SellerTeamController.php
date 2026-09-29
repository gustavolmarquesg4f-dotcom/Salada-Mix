<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\User;
use App\Notifications\SellerInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SellerTeamController extends Controller
{
    private const ROLES = ['manager', 'operations', 'finance', 'support'];

    public function index(Seller $seller, Request $request): View
    {
        $this->requireOwner($seller, $request);
        $members = $seller->memberships()->with('user')->orderBy('created_at')->get();
        $invitations = DB::table('seller_invitations')->where('seller_id', $seller->id)
            ->whereNull('cancelled_at')->whereNull('accepted_at')
            ->where('expires_at', '>', now())->orderByDesc('created_at')->get();

        return view('seller.team', compact('seller', 'members', 'invitations'));
    }

    public function invite(Seller $seller, Request $request): RedirectResponse
    {
        $this->requireOwner($seller, $request);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);
        $email = Str::lower(trim($data['email']));
        $token = bin2hex(random_bytes(32));

        $id = DB::transaction(function () use ($seller, $request, $data, $email, $token): string {
            DB::table('sellers')->where('id', $seller->id)->lockForUpdate()->firstOrFail();
            $hasMember = SellerMembership::query()->where('seller_id', $seller->id)
                ->whereHas('user', fn ($query) => $query->where('email', $email))
                ->where('status', 'active')->exists();

            if ($hasMember) {
                throw ValidationException::withMessages(['email' => 'Esse e-mail já integra a empresa.']);
            }

            $hasPending = DB::table('seller_invitations')->where('seller_id', $seller->id)
                ->where('email', $email)->whereNull('accepted_at')->whereNull('cancelled_at')
                ->where('expires_at', '>', now())->exists();

            if ($hasPending) {
                throw ValidationException::withMessages(['email' => 'Já existe um convite vigente para esse e-mail.']);
            }

            $id = (string) Str::ulid();
            DB::table('seller_invitations')->insert([
                'id' => $id, 'seller_id' => $seller->id, 'invited_by' => $request->user()->id,
                'email' => $email, 'role' => $data['role'], 'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(48), 'created_at' => now(), 'updated_at' => now(),
            ]);
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id, 'seller_id' => $seller->id,
                'action' => 'seller.team.invited', 'metadata' => ['email' => $email, 'role' => $data['role']],
            ]);

            return $id;
        }, 3);

        Notification::route('mail', $email)->notify(new SellerInvitationNotification(
            $seller->trade_name,
            route('seller.team.accept.show', ['invitation' => $id, 'token' => $token])
        ));

        return back()->with('status', 'Convite enviado para o e-mail informado.');
    }

    public function showInvitation(Request $request, string $invitation, string $token): View
    {
        $record = $this->validatedInvitation($request, $invitation, $token);

        return view('seller.invitation', ['invitation' => $record, 'token' => $token]);
    }

    public function accept(Request $request, string $invitation, string $token): RedirectResponse
    {
        $record = $this->validatedInvitation($request, $invitation, $token);
        DB::transaction(function () use ($request, $record, $token): void {
            $locked = DB::table('seller_invitations')->where('id', $record->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->accepted_at === null && $locked->cancelled_at === null &&
                now()->lessThan($locked->expires_at) &&
                hash_equals($locked->token_hash, hash('sha256', $token)), 404);

            $membership = SellerMembership::query()->firstOrNew([
                'seller_id' => $locked->seller_id, 'user_id' => $request->user()->id,
            ]);
            // An owner can never be downgraded through a pending invitation.
            abort_if($membership->exists && $membership->role === 'owner', 403);
            $membership->fill(['role' => $locked->role, 'status' => 'active'])->save();
            DB::table('seller_invitations')->where('id', $locked->id)
                ->update(['accepted_at' => now(), 'updated_at' => now()]);
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id, 'seller_id' => $locked->seller_id,
                'action' => 'seller.team.accepted', 'metadata' => ['role' => $locked->role],
            ]);
        }, 3);

        return redirect()->route('seller.dashboard', $record->seller_id)
            ->with('status', 'Você ingressou na equipe.');
    }

    public function cancel(Seller $seller, Request $request, string $invitation): RedirectResponse
    {
        $this->requireOwner($seller, $request);
        $changed = DB::table('seller_invitations')->where('id', $invitation)
            ->where('seller_id', $seller->id)->whereNull('accepted_at')->whereNull('cancelled_at')
            ->update(['cancelled_at' => now(), 'updated_at' => now()]);
        abort_unless($changed, 404);
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id, 'seller_id' => $seller->id,
            'action' => 'seller.team.invitation_cancelled', 'metadata' => ['invitation_id' => $invitation],
        ]);

        return back()->with('status', 'Convite cancelado.');
    }

    public function remove(Seller $seller, Request $request, SellerMembership $membership): RedirectResponse
    {
        $this->requireOwner($seller, $request);
        abort_unless($membership->seller_id === $seller->id, 404);
        abort_if($membership->role === 'owner', 403);
        $membership->update(['status' => 'revoked']);
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id, 'seller_id' => $seller->id,
            'action' => 'seller.team.member_revoked',
            'metadata' => ['member_user_id' => $membership->user_id],
        ]);

        return back()->with('status', 'Acesso do membro revogado.');
    }

    private function requireOwner(Seller $seller, Request $request): void
    {
        abort_unless($seller->memberships()->where('user_id', $request->user()->id)
            ->where('status', 'active')->where('role', 'owner')->exists(), 403);
    }

    private function validatedInvitation(Request $request, string $invitation, string $token): object
    {
        $record = DB::table('seller_invitations')->where('id', $invitation)->first();
        abort_unless($record && $record->accepted_at === null && $record->cancelled_at === null, 404);
        abort_unless(now()->lessThan($record->expires_at), 404);
        abort_unless(hash_equals($record->token_hash, hash('sha256', $token)), 404);
        abort_unless(Str::lower((string) $request->user()->email) === $record->email, 403);

        return $record;
    }
}
