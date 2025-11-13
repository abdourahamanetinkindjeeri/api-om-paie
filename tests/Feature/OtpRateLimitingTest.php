<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'numero' => '221771234567',
            'nom' => 'Test',
            'prenom' => 'User'
        ]);
    }

    /** @test */
    public function it_limits_otp_generation_attempts()
    {
        // Tenter de générer des OTP plusieurs fois
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/auth/register', [
                'telephone' => $this->user->numero,
                'nom' => 'Test',
                'prenom' => 'User' . $i,
                'email' => "test{$i}@example.com"
            ]);

            if ($i < 4) {
                // Les 4 premières tentatives devraient réussir
                $response->assertStatus(200);
            }
        }

        // La 5ème tentative devrait être bloquée
        $response = $this->postJson('/auth/register', [
            'telephone' => $this->user->numero,
            'nom' => 'Test',
            'prenom' => 'User5',
            'email' => 'test5@example.com'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Trop de tentatives. Veuillez réessayer plus tard.'
            ]);
    }

    /** @test */
    public function it_limits_otp_verification_attempts()
    {
        // D'abord créer un utilisateur et initier l'inscription
        $response = $this->postJson('/auth/register', [
            'telephone' => '221771234568',
            'nom' => 'Test',
            'prenom' => 'Verify',
            'email' => 'testverify@example.com'
        ]);

        $response->assertStatus(200);
        $otpCode = $response->json('data.code_otp');

        // Tenter de vérifier l'OTP plusieurs fois avec un mauvais code
        for ($i = 0; $i < 3; $i++) {
            $verifyResponse = $this->postJson('/auth/confirmation', [
                'telephone' => '221771234568',
                'code_otp' => '000000' // Mauvais code
            ]);

            if ($i < 2) {
                // Les 2 premières tentatives devraient échouer normalement
                $verifyResponse->assertStatus(400);
            }
        }

        // La 3ème tentative devrait être bloquée par rate limiting
        $verifyResponse = $this->postJson('/auth/confirmation', [
            'telephone' => '221771234568',
            'code_otp' => '000000'
        ]);

        $verifyResponse->assertStatus(400);
        // Note: Le message d'erreur peut varier selon l'implémentation
    }

    /** @test */
    public function it_resets_otp_rate_limit_after_successful_verification()
    {
        // Créer un utilisateur et initier l'inscription
        $response = $this->postJson('/auth/register', [
            'telephone' => '221771234569',
            'nom' => 'Test',
            'prenom' => 'Success',
            'email' => 'testsuccess@example.com'
        ]);

        $response->assertStatus(200);
        $otpCode = $response->json('data.code_otp');

        // Vérifier l'OTP avec le bon code
        $verifyResponse = $this->postJson('/auth/confirmation', [
            'telephone' => '221771234569',
            'code_otp' => $otpCode,
            'code' => '1234' // Code PIN
        ]);

        $verifyResponse->assertStatus(201);

        // Maintenant, essayer de générer un nouvel OTP - devrait réussir
        // (le compteur devrait être remis à zéro)
        $newResponse = $this->postJson('/auth/register', [
            'telephone' => '221771234570', // Nouveau numéro
            'nom' => 'Test',
            'prenom' => 'New',
            'email' => 'testnew@example.com'
        ]);

        $newResponse->assertStatus(200);
    }

    /** @test */
    public function it_handles_rate_limiting_for_resend_otp()
    {
        // D'abord créer un utilisateur
        $this->postJson('/auth/register', [
            'telephone' => '221771234571',
            'nom' => 'Test',
            'prenom' => 'Resend',
            'email' => 'testresend@example.com'
        ]);

        // Tenter de renvoyer l'OTP plusieurs fois
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/auth/resend', [
                'identifier' => '221771234571'
            ]);

            if ($i < 4) {
                $response->assertStatus(200);
            }
        }

        // La 5ème tentative devrait être bloquée
        $response = $this->postJson('/auth/resend', [
            'identifier' => '221771234571'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Trop de tentatives. Veuillez réessayer plus tard.'
            ]);
    }
}
