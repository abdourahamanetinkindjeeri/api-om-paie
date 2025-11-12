<?php

namespace Tests\Unit\Rules;

use App\Rules\ActiveMerchant;
use App\Repositories\Contracts\MerchantRepositoryInterface;
use App\Models\Merchant;
use Tests\TestCase;
use Mockery;

class ActiveMerchantTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_rejects_invalid_format_too_short()
    {
        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertFalse($rule->passes('code_marchand', 'ABC'));
        $this->assertStringContainsString('6 et 20 caractères', $rule->message());
    }

    public function test_rejects_invalid_format_lowercase()
    {
        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertFalse($rule->passes('code_marchand', 'abcdef'));
        $this->assertStringContainsString('6 et 20 caractères', $rule->message());
    }

    public function test_rejects_invalid_format_special_chars()
    {
        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertFalse($rule->passes('code_marchand', 'MARCH@123'));
        $this->assertStringContainsString('6 et 20 caractères', $rule->message());
    }

    public function test_rejects_non_existent_merchant()
    {
        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $merchantRepo->shouldReceive('findByCode')
            ->with('NOTFOUND123')
            ->once()
            ->andReturn(null);

        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertFalse($rule->passes('code_marchand', 'NOTFOUND123'));
        $this->assertEquals('Ce code marchand n\'existe pas.', $rule->message());
    }

    public function test_rejects_inactive_merchant()
    {
        $merchant = new Merchant([
            'code' => 'INACTIVE123',
            'status' => 'inactive',
            'name' => 'Test Merchant'
        ]);

        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $merchantRepo->shouldReceive('findByCode')
            ->with('INACTIVE123')
            ->once()
            ->andReturn($merchant);

        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertFalse($rule->passes('code_marchand', 'INACTIVE123'));
        $this->assertEquals('Ce code marchand est actuellement inactif.', $rule->message());
    }

    public function test_accepts_valid_active_merchant()
    {
        $merchant = new Merchant([
            'code' => 'ACTIVE123',
            'status' => 'active',
            'name' => 'Test Merchant'
        ]);

        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $merchantRepo->shouldReceive('findByCode')
            ->with('ACTIVE123')
            ->once()
            ->andReturn($merchant);

        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertTrue($rule->passes('code_marchand', 'ACTIVE123'));
    }

    public function test_accepts_alphanumeric_codes()
    {
        $merchant = new Merchant([
            'code' => 'ABC123XYZ',
            'status' => 'active',
            'name' => 'Test Merchant'
        ]);

        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $merchantRepo->shouldReceive('findByCode')
            ->with('ABC123XYZ')
            ->once()
            ->andReturn($merchant);

        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertTrue($rule->passes('code_marchand', 'ABC123XYZ'));
    }

    public function test_accepts_maximum_length_code()
    {
        $merchant = new Merchant([
            'code' => 'ABCDEFGHIJ1234567890', // 20 caractères
            'status' => 'active',
            'name' => 'Test Merchant'
        ]);

        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $merchantRepo->shouldReceive('findByCode')
            ->with('ABCDEFGHIJ1234567890')
            ->once()
            ->andReturn($merchant);

        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertTrue($rule->passes('code_marchand', 'ABCDEFGHIJ1234567890'));
    }

    public function test_rejects_code_exceeding_maximum_length()
    {
        $merchantRepo = Mockery::mock(MerchantRepositoryInterface::class);
        $this->app->instance(MerchantRepositoryInterface::class, $merchantRepo);

        $rule = new ActiveMerchant();

        $this->assertFalse($rule->passes('code_marchand', 'ABCDEFGHIJ12345678901')); // 21 caractères
        $this->assertStringContainsString('6 et 20 caractères', $rule->message());
    }
}
