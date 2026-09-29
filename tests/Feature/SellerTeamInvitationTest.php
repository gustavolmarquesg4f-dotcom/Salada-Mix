<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\User;
use App\Notifications\SellerInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SellerTeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function seller(User $owner): Seller
    {
        $seller = Seller::query()->create([
            'owner_user_id' => $owner->id,
            'legal_name' => 'Loja Teste Ltda',
            'trade_name' => 'Loja Teste',
            'cnpj' => '11222333000181',
            'contact_email' => 'contato@example.test',
            'status' => 'approved',
        ]);

        SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $owner->id,
            'role' => 'owner', 'status' => 'active',
        ]);

        return $seller;
    }

    public function test_only_owner_can_invite_and_cancel_team_members(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $seller = $this->seller($owner);
        SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $manager->id,
            'role' => 'manager', 'status' => 'active',
        ]);
        $data = ['email' => 'new.member@example.test', 'role' => 'operations'];

        $this->actingAs($manager)->post(route('seller.team.invite', $seller), $data)->assertForbidden();
        $this->actingAs($owner)->post(route('seller.team.invite', $seller), $data)->assertRedirect();
        $this->assertDatabaseHas('seller_invitations', [
            'seller_id' => $seller->id, 'email' => $data['email'], 'role' => 'operations',
        ]);
        Notification::assertSentOnDemand(SellerInvitationNotification::class);
        $invitation = DB::table('seller_invitations')->first();
        $this->actingAs($manager)->delete(route('seller.team.cancel', [$seller, $invitation->id]))->assertForbidden();
        $this->actingAs($owner)->delete(route('seller.team.cancel', [$seller, $invitation->id]))->assertRedirect();
        $this->assertNotNull(DB::table('seller_invitations')->value('cancelled_at'));
    }

    public function test_email_mismatch_cannot_accept_and_owner_cannot_be_revoked(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'invitee@example.test']);
        $stranger = User::factory()->create();
        $seller = $this->seller($owner);

        $this->actingAs($owner)->post(route('seller.team.invite', $seller), [
            'email' => $invitee->email, 'role' => 'finance',
        ])->assertRedirect();
        $url = null;
        Notification::assertSentOnDemand(SellerInvitationNotification::class, function ($notification) use (&$url) {
            $url = $notification->acceptUrl;

            return true;
        });
        $this->assertNotNull($url);
        $this->actingAs($stranger)->get($url)->assertForbidden();
        $this->actingAs($stranger)->post($url)->assertForbidden();
        $this->actingAs($invitee)->get($url)->assertOk();
        $this->actingAs($invitee)->post($url)->assertRedirect(route('seller.dashboard', $seller));
        $this->assertDatabaseHas('seller_memberships', [
            'seller_id' => $seller->id, 'user_id' => $invitee->id, 'role' => 'finance', 'status' => 'active',
        ]);
        $this->actingAs($invitee)->post($url)->assertNotFound();

        $ownerMembership = SellerMembership::query()
            ->where('seller_id', $seller->id)->where('user_id', $owner->id)->firstOrFail();
        $this->actingAs($owner)->delete(route('seller.team.remove', [$seller, $ownerMembership]))
            ->assertForbidden();
    }

    public function test_cross_seller_revoke_is_not_found(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $member = User::factory()->create();
        $sellerA = $this->seller($owner);
        $sellerB = Seller::query()->create([
            'owner_user_id' => $otherOwner->id,
            'legal_name' => 'Outra Empresa Ltda', 'trade_name' => 'Outra Empresa',
            'cnpj' => '00000000E08G12', 'contact_email' => 'outra@example.test', 'status' => 'approved',
        ]);
        $membership = SellerMembership::query()->create([
            'seller_id' => $sellerB->id, 'user_id' => $member->id,
            'role' => 'operations', 'status' => 'active',
        ]);
        $this->actingAs($owner)->delete(route('seller.team.remove', [$sellerA, $membership]))
            ->assertNotFound();
        $this->assertDatabaseHas('seller_memberships', ['id' => $membership->id, 'status' => 'active']);
    }

    public function test_owner_can_view_team_and_duplicate_invites_are_rejected(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $seller = $this->seller($owner);
        $this->actingAs($owner)->get(route('seller.team.index', $seller))
            ->assertOk()->assertSee('Equipe');
        $this->post(route('seller.team.invite', $seller), [
            'email' => 'member@example.test', 'role' => 'support',
        ])->assertRedirect();
        $this->post(route('seller.team.invite', $seller), [
            'email' => 'member@example.test', 'role' => 'support',
        ])->assertSessionHasErrors('email');
        $this->assertSame(1, DB::table('seller_invitations')->where('seller_id', $seller->id)->count());
    }

    public function test_invalid_token_is_rejected_and_owner_can_revoke_another_member(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.test']);
        $seller = $this->seller($owner);
        $membership = SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $member->id,
            'role' => 'support', 'status' => 'active',
        ]);
        $this->actingAs($owner)->post(route('seller.team.invite', $seller), [
            'email' => 'another@example.test', 'role' => 'operations',
        ])->assertRedirect();
        $invitation = DB::table('seller_invitations')->where('seller_id', $seller->id)->firstOrFail();
        $this->actingAs($member)->get(route('seller.team.accept.show', [
            'invitation' => $invitation->id, 'token' => 'invalid',
        ]))->assertNotFound();
        $this->actingAs($owner)->delete(route('seller.team.remove', [$seller, $membership]))
            ->assertRedirect();
        $this->assertDatabaseHas('seller_memberships', [
            'id' => $membership->id, 'status' => 'revoked',
        ]);
    }
}
