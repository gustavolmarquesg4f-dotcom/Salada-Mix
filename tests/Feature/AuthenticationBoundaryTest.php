<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_does_not_grant_platform_admin_permissions(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Comprador Teste',
            'email' => 'teste@example.test',
            'password' => 'SenhaForte2026!',
            'password_confirmation' => 'SenhaForte2026!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'teste@example.test',
            'platform_role' => 'customer',
        ]);
        $this->assertAuthenticated();
    }

    public function test_unverified_user_cannot_register_a_company(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('seller.apply'))
            ->assertRedirect(route('verification.notice'));
    }
}

