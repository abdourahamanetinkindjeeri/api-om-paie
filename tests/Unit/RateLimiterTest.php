<?php

namespace Tests\Unit;

use App\Services\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class RateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = app(RateLimiter::class);
        Cache::flush(); // Nettoyer le cache avant chaque test
    }

    /** @test */
    public function it_allows_attempts_within_limit()
    {
        $key = 'test_user';

        // Première tentative - devrait réussir
        $result1 = $this->rateLimiter->attempt($key, 'otp_generation');
        $this->assertTrue($result1['allowed']);
        $this->assertEquals(4, $result1['remaining_attempts']); // 5 max - 1 utilisé

        // Deuxième tentative - devrait réussir
        $result2 = $this->rateLimiter->attempt($key, 'otp_generation');
        $this->assertTrue($result2['allowed']);
        $this->assertEquals(3, $result2['remaining_attempts']);
    }

    /** @test */
    public function it_blocks_attempts_over_limit()
    {
        $key = 'test_user_blocked';

        // Consommer toutes les tentatives
        for ($i = 0; $i < 5; $i++) {
            $result = $this->rateLimiter->attempt($key, 'otp_generation');
            if ($i < 4) {
                $this->assertTrue($result['allowed']);
            }
        }

        // La 6ème tentative devrait être bloquée
        $result = $this->rateLimiter->attempt($key, 'otp_generation');
        $this->assertFalse($result['allowed']);
        $this->assertEquals('rate_limit_exceeded', $result['block_reason']);
        $this->assertArrayHasKey('blocked_until', $result);
    }

    /** @test */
    public function it_resets_counter_after_success()
    {
        $key = 'test_user_reset';

        // Faire quelques tentatives
        $this->rateLimiter->recordAttempt($key, 'otp_verification');
        $this->rateLimiter->recordAttempt($key, 'otp_verification');

        // Vérifier qu'il y a des tentatives enregistrées
        $stats = $this->rateLimiter->getStats($key, 'otp_verification');
        $this->assertEquals(2, $stats['attempts']);

        // Reset du compteur
        $this->rateLimiter->reset($key, 'otp_verification');

        // Vérifier que le compteur est à zéro
        $stats = $this->rateLimiter->getStats($key, 'otp_verification');
        $this->assertEquals(0, $stats['attempts']);
        $this->assertFalse($stats['is_blocked']);
    }

    /** @test */
    public function it_handles_different_operation_types()
    {
        $key = 'test_user_multi';

        // Test OTP generation
        $result1 = $this->rateLimiter->attempt($key, 'otp_generation');
        $this->assertTrue($result1['allowed']);

        // Test login attempts (devrait être séparé)
        $result2 = $this->rateLimiter->attempt($key, 'login_attempts');
        $this->assertTrue($result2['allowed']);

        // Vérifier que les compteurs sont séparés
        $otpStats = $this->rateLimiter->getStats($key, 'otp_generation');
        $loginStats = $this->rateLimiter->getStats($key, 'login_attempts');

        $this->assertEquals(1, $otpStats['attempts']);
        $this->assertEquals(1, $loginStats['attempts']);
    }

    /** @test */
    public function it_provides_detailed_stats()
    {
        $key = 'test_user_stats';

        $stats = $this->rateLimiter->getStats($key, 'otp_generation');

        $this->assertArrayHasKey('attempts', $stats);
        $this->assertArrayHasKey('max_attempts', $stats);
        $this->assertArrayHasKey('remaining_attempts', $stats);
        $this->assertArrayHasKey('window_minutes', $stats);
        $this->assertArrayHasKey('block_minutes', $stats);
        $this->assertArrayHasKey('is_blocked', $stats);
        $this->assertArrayHasKey('blocked_until', $stats);
        $this->assertArrayHasKey('next_reset', $stats);

        $this->assertEquals(0, $stats['attempts']);
        $this->assertEquals(5, $stats['max_attempts']);
        $this->assertEquals(5, $stats['remaining_attempts']);
        $this->assertFalse($stats['is_blocked']);
    }

    /** @test */
    public function it_handles_otp_verification_rate_limiting()
    {
        $key = 'test_otp_verify';

        // OTP verification a une limite de 3 tentatives
        for ($i = 0; $i < 3; $i++) {
            $result = $this->rateLimiter->attempt($key, 'otp_verification');
            $this->assertTrue($result['allowed']);
        }

        // La 4ème tentative devrait être bloquée
        $result = $this->rateLimiter->attempt($key, 'otp_verification');
        $this->assertFalse($result['allowed']);
    }

    /** @test */
    public function it_handles_login_rate_limiting()
    {
        $key = 'test_login';

        // Login attempts a une limite de 5 tentatives
        for ($i = 0; $i < 5; $i++) {
            $result = $this->rateLimiter->attempt($key, 'login_attempts');
            $this->assertTrue($result['allowed']);
        }

        // La 6ème tentative devrait être bloquée
        $result = $this->rateLimiter->attempt($key, 'login_attempts');
        $this->assertFalse($result['allowed']);
    }

    /** @test */
    public function it_can_check_without_recording_attempt()
    {
        $key = 'test_check_only';

        // Vérifier sans enregistrer
        $check1 = $this->rateLimiter->check($key, 'otp_generation');
        $this->assertTrue($check1['allowed']);

        // Vérifier que rien n'a été enregistré
        $stats = $this->rateLimiter->getStats($key, 'otp_generation');
        $this->assertEquals(0, $stats['attempts']);

        // Maintenant enregistrer manuellement
        $this->rateLimiter->recordAttempt($key, 'otp_generation');

        $stats = $this->rateLimiter->getStats($key, 'otp_generation');
        $this->assertEquals(1, $stats['attempts']);
    }
}
