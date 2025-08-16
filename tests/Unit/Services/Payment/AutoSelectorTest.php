<?php

namespace Tests\Unit\Services\Payment;

use App\Services\Payment\AutoSelector;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AutoSelectorTest extends TestCase
{
    #[Test]
    public function selects_wallet_when_sufficient_balance(): void
    {
    [$method, $crypto] = AutoSelector::determine(['wallet','nowpayments','stripe'], 50, 40);
        $this->assertSame('wallet', $method);
    $this->assertNull($crypto);
    }

    #[Test]
    public function falls_back_to_crypto_when_wallet_insufficient(): void
    {
    [$method, $crypto] = AutoSelector::determine(['wallet','nowpayments','stripe'], 10, 40);
    $this->assertSame('crypto', $method);
    $this->assertSame('xmr', $crypto);
    }

    #[Test]
    public function selects_crypto_when_wallet_inactive(): void
    {
    [$method, $crypto] = AutoSelector::determine(['nowpayments','stripe'], 100, 40);
    $this->assertSame('crypto', $method);
    $this->assertSame('xmr', $crypto);
    }

    #[Test]
    public function falls_back_to_first_active_when_no_wallet_no_crypto(): void
    {
    [$method, $crypto] = AutoSelector::determine(['stripe','mir'], 100, 40);
        $this->assertSame('stripe', $method);
        $this->assertNull($crypto);
    }

    #[Test]
    public function returns_nulls_when_no_active_methods(): void
    {
        [$method, $crypto] = AutoSelector::determine([], 100, 40);
        $this->assertNull($method);
        $this->assertNull($crypto);
    }
}
