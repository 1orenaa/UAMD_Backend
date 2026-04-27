<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    //  REGJISTRIM
    // ─────────────────────────────────────────────────────────────────────────

    public function test_register_krijon_user_dhe_kthen_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@uni.edu',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'student',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.email', 'test@uni.edu')
            ->assertJsonPath('user.role', 'student');

        $this->assertDatabaseHas('users', [
            'email' => 'test@uni.edu',
            'role'  => 'student',
        ]);
    }

    public function test_register_refuzon_email_duplikat(): void
    {
        User::create([
            'name'     => 'Ekzistues',
            'email'    => 'dup@uni.edu',
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Tjeter',
            'email'                 => 'dup@uni.edu',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'student',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_refuzon_role_te_palejueshem(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'x@uni.edu',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'role'                  => 'admin', // admin nuk lejohet publikisht
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_register_kerkon_fjalkalim_te_konfirmuar(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'y@uni.edu',
            'password'              => 'secret123',
            'password_confirmation' => 'ndryshe88',
            'role'                  => 'student',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  LOGIN
    // ─────────────────────────────────────────────────────────────────────────

    public function test_login_me_kredenciale_te_sakta_kthen_token(): void
    {
        User::create([
            'name'     => 'User',
            'email'    => 'login@uni.edu',
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'login@uni.edu',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'user'])
            ->assertJsonPath('user.email', 'login@uni.edu');
    }

    public function test_login_me_fjalkalim_te_gabuar_kthen_401(): void
    {
        User::create([
            'name'     => 'User',
            'email'    => 'wrong@uni.edu',
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'wrong@uni.edu',
            'password' => 'gabim-fjal',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Email ose fjalëkalimi është i gabuar.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  /me DHE LOGOUT
    // ─────────────────────────────────────────────────────────────────────────

    public function test_me_kerkon_autentikim(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }

    public function test_me_kthen_useri_e_autentikuar(): void
    {
        $user = User::create([
            'name'     => 'Me Tester',
            'email'    => 'me@uni.edu',
            'password' => Hash::make('secret123'),
            'role'     => 'pedagog',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('email', 'me@uni.edu')
            ->assertJsonPath('role', 'pedagog');
    }

    public function test_logout_fshin_tokenin_aktual(): void
    {
        $user = User::create([
            'name'     => 'Logout Tester',
            'email'    => 'logout@uni.edu',
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);

        $newToken = $user->createToken('test');
        $tokenId  = $newToken->accessToken->id;
        $token    = $newToken->plainTextToken;

        // Para logout, tokeni ekziston ne DB
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Logout u krye me sukses!');

        // Pas logout, rreshti i tokenit duhet te jete fshire nga DB
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FORGOT / RESET PASSWORD
    // ─────────────────────────────────────────────────────────────────────────

    public function test_forgot_password_gjeneron_token_per_user_ekzistues(): void
    {
        User::create([
            'name'     => 'FP User',
            'email'    => 'fp@uni.edu',
            'password' => Hash::make('secret123'),
            'role'     => 'student',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'fp@uni.edu',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'fp@uni.edu',
        ]);
    }

    public function test_forgot_password_kthen_200_edhe_per_email_qe_nuk_ekziston(): void
    {
        // Mos ekspozo ekzistencen e emailit — duhet te kthehet perdhese 200.
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'ska@askund.al',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'ska@askund.al',
        ]);
    }

    public function test_reset_password_me_token_te_vlefshem_ndryshon_fjalekalimin(): void
    {
        $user = User::create([
            'name'     => 'RP User',
            'email'    => 'rp@uni.edu',
            'password' => Hash::make('origjinali8'),
            'role'     => 'student',
        ]);

        // Gjenero token duke kalur nga forgot-password (ne debug mode kthen tokenin).
        config(['app.debug' => true]);
        $fp = $this->postJson('/api/auth/forgot-password', ['email' => 'rp@uni.edu']);
        $token = $fp->json('token');
        $this->assertNotEmpty($token);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'rp@uni.edu',
            'token'                 => $token,
            'password'              => 'fjalkalim9',
            'password_confirmation' => 'fjalkalim9',
        ]);

        $response->assertStatus(200);

        // Fjalekalimi i ri funksionon
        $this->postJson('/api/auth/login', [
            'email'    => 'rp@uni.edu',
            'password' => 'fjalkalim9',
        ])->assertStatus(200);

        // Tokeni u fshi
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'rp@uni.edu',
        ]);
    }

    public function test_reset_password_refuzon_token_te_gabuar(): void
    {
        User::create([
            'name'     => 'Bad Token',
            'email'    => 'bt@uni.edu',
            'password' => Hash::make('origjinali8'),
            'role'     => 'student',
        ]);

        // Krijo nje token te ligjshem me nje email ndryshe
        DB::table('password_reset_tokens')->insert([
            'email'      => 'bt@uni.edu',
            'token'      => Hash::make('token-i-vertete'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'bt@uni.edu',
            'token'                 => 'token-i-gabuar',
            'password'              => 'fjalkalim9',
            'password_confirmation' => 'fjalkalim9',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_valideshte_fjalkalimin_dhe_kerkon_konfirmim(): void
    {
        User::create([
            'name'     => 'Weak PW',
            'email'    => 'weak@uni.edu',
            'password' => Hash::make('origjinali8'),
            'role'     => 'student',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email'      => 'weak@uni.edu',
            'token'      => Hash::make('tokeni'),
            'created_at' => now(),
        ]);

        // Fjalekalim pa numra
        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'weak@uni.edu',
            'token'                 => 'tokeni',
            'password'              => 'vetemshkronja',
            'password_confirmation' => 'vetemshkronja',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
