<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApiError;
use Openpay\Data\OpenpayApiRequestError;
use Openpay\Data\OpenpayApiResourceBase;
use Openpay\Resources\OpenpayCharge;
use Openpay\Resources\OpenpayCustomer;
use PHPUnit\Framework\TestCase;

final class OpenpayResourceFlowTest extends TestCase
{
    private FakeOpenpayHttpTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        Openpay::reset();
        $this->transport = new FakeOpenpayHttpTransport();
        Openpay::setHttpTransport($this->transport);
    }

    protected function tearDown(): void
    {
        Openpay::reset();
        parent::tearDown();
    }

    public function testCustomerChargePlanWebhookAndNestedLists(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');

        $this->transport->enqueue(json_encode([
            'id' => 'cusaaaaaaaaaaaaaaaa',
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'status' => 'active',
            'card' => ['id' => 'cardzzzzzzzzzzzzzzzz', 'brand' => 'visa', 'type' => 'debit'],
            'metadata' => ['k' => 'v'],
        ], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada', 'email' => 'ada@example.test']);
        self::assertInstanceOf(OpenpayCustomer::class, $customer);
        self::assertSame('Ada', $customer->name);
        self::assertSame('visa', $customer->card->brand);
        self::assertSame('v', $customer->metadata->k);

        $this->transport->enqueue(json_encode([
            ['id' => 'cusbbbbbbbbbbbbbbbb', 'name' => 'Bob'],
        ], \JSON_THROW_ON_ERROR));
        $list = $openpay->customers->getList(['limit' => 1]);
        self::assertCount(1, $list);

        $this->transport->enqueue(json_encode([
            'id' => 'cusaaaaaaaaaaaaaaaa',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
        ], \JSON_THROW_ON_ERROR));
        $customer->name = 'Ada Lovelace';
        $customer->save();
        self::assertSame('Ada Lovelace', $customer->name);

        $this->transport->enqueue(json_encode([
            'id' => 'cardaaaaaaaaaaaaaaaa',
            'brand' => 'mastercard',
        ], \JSON_THROW_ON_ERROR));
        $card = $customer->cards->add(['token_id' => 'tokaaaaaaaaaaaaaaaa']);
        $this->transport->enqueue('{"points":10}');
        self::assertSame(10, $card->get('points')->points);
        $this->transport->enqueue('');
        $card->delete();

        $this->transport->enqueue(json_encode([
            'id' => 'chaaaaaaaaaaaaaaaaaa',
            'status' => 'completed',
        ], \JSON_THROW_ON_ERROR));
        $charge = $openpay->charges->create(['amount' => 100, 'method' => 'card']);
        self::assertInstanceOf(OpenpayCharge::class, $charge);

        $this->transport->enqueue(json_encode(['id' => 'refaaaaaaaaaaaaaaaaa', 'amount' => 100], \JSON_THROW_ON_ERROR));
        $charge->refund(['amount' => 100]);
        $this->transport->enqueue(json_encode(['id' => 'capaaaaaaaaaaaaaaaaa', 'amount' => 100], \JSON_THROW_ON_ERROR));
        $charge->capture(['amount' => 100]);
        $this->transport->enqueue(json_encode(['id' => 'chaaaaaaaaaaaaaaaaaa', 'status' => 'updated'], \JSON_THROW_ON_ERROR));
        $charge->update(['description' => 'x']);

        $this->transport->enqueue(json_encode(['id' => 'feeaaaaaaaaaaaaaaaaa', 'status' => 'ok'], \JSON_THROW_ON_ERROR));
        $fee = $openpay->fees->create(['amount' => 10]);
        $this->transport->enqueue(json_encode(['id' => 'refbbbbbbbbbbbbbbbbb'], \JSON_THROW_ON_ERROR));
        $fee->refund(['amount' => 10]);

        $this->transport->enqueue(json_encode(['id' => 'plnaaaaaaaaaaaaaaaaa', 'amount' => 99], \JSON_THROW_ON_ERROR));
        $plan = $openpay->plans->add(['amount' => 99, 'repeat_every' => 1, 'repeat_unit' => 'month']);
        $this->transport->enqueue(json_encode(['id' => 'plnaaaaaaaaaaaaaaaaa', 'amount' => 120], \JSON_THROW_ON_ERROR));
        $plan->save();
        $this->transport->enqueue(json_encode(['id' => 'subaaaaaaaaaaaaaaaaa', 'plan_id' => 'plnaaaaaaaaaaaaaaaaa'], \JSON_THROW_ON_ERROR));
        $subscription = $plan->subscriptions->create(['customer_id' => 'cusaaaaaaaaaaaaaaaa']);
        $subscription->source_id = 'srcaaaaaaaaaaaaaaaa';
        $this->transport->enqueue(json_encode(['id' => 'subaaaaaaaaaaaaaaaaa', 'plan_id' => 'plnaaaaaaaaaaaaaaaaa'], \JSON_THROW_ON_ERROR));
        $subscription->save();
        $this->transport->enqueue('');
        $subscription->delete();
        $this->transport->enqueue('');
        $plan->delete();

        $this->transport->enqueue(json_encode(['id' => 'whaaaaaaaaaaaaaaaaaa', 'url' => 'https://example.test'], \JSON_THROW_ON_ERROR));
        $hook = $openpay->webhooks->add(['url' => 'https://example.test', 'event_types' => ['charge.succeeded']]);
        $this->transport->enqueue(json_encode(['id' => 'whaaaaaaaaaaaaaaaaaa', 'url' => 'https://example.test/2'], \JSON_THROW_ON_ERROR));
        $hook->save();
        $this->transport->enqueue('');
        $hook->delete();

        $this->transport->enqueue(json_encode(['id' => 'tokaaaaaaaaaaaaaaaaa', 'card' => ['brand' => 'visa']], \JSON_THROW_ON_ERROR));
        $token = $openpay->tokens->add(['card_number' => '4111111111111111']);
        $this->transport->enqueue('{"brand":"visa"}');
        $token->get('brand');

        $this->transport->enqueue(json_encode(['id' => 'bin411111', 'bank' => 'x'], \JSON_THROW_ON_ERROR));
        $bine = $openpay->bines->get('411111');
        self::assertSame('411111', $bine->id);

        $this->transport->enqueue(json_encode(['id' => 'payoutaaaaaaaaaaaaaa', 'status' => 'in_progress'], \JSON_THROW_ON_ERROR));
        $openpay->payouts->create(['amount' => 50]);
        $this->transport->enqueue(json_encode(['id' => 'trfaaaaaaaaaaaaaaaaa', 'status' => 'ok'], \JSON_THROW_ON_ERROR));
        $customer->transfers->create(['amount' => 5, 'customer_id' => 'cusbbbbbbbbbbbbbbbb']);
        $this->transport->enqueue(json_encode(['id' => 'pseaaaaaaaaaaaaaaaaa', 'status' => 'ok'], \JSON_THROW_ON_ERROR));
        $openpay->pses->create(['amount' => 20]);
        $this->transport->enqueue(json_encode(['id' => 'baaaaaaaaaaaaaaaaaaa', 'bank_code' => '012'], \JSON_THROW_ON_ERROR));
        $bank = $customer->bankaccounts->add(['clabe' => '012345678901234567']);
        $this->transport->enqueue('');
        $bank->delete();

        $this->transport->enqueue('');
        $customer->delete();

        self::assertNotEmpty($this->transport->calls);
    }

    public function testMagicGetSetAndValidationErrors(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada', 'status' => 'active'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada']);

        self::assertNull($customer->missing_property);

        $this->expectException(OpenpayApiError::class);
        $customer->status = ['array'];
    }

    public function testInvalidConstructorParams(): void
    {
        $ref = new \ReflectionClass(OpenpayCustomer::class);
        $instance = $ref->newInstanceWithoutConstructor();
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);
        $this->expectException(OpenpayApiError::class);
        $this->expectExceptionMessage('Invalid parameter type');
        $ctor->invoke($instance, OpenpayCustomer::class, 'not-array');
    }

    public function testInvalidResourceClass(): void
    {
        $this->expectException(OpenpayApiError::class);
        $this->expectExceptionMessage('Invalid Openpay resource type');
        (new \ReflectionMethod(OpenpayApiResourceBase::class, 'getInstance'))
            ->invoke(null, 'Openpay\\Resources\\DoesNotExist');
    }

    public function testInvalidIdAndParams(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        try {
            $openpay->customers->get('BAD ID');
            self::fail('expected invalid id');
        } catch (OpenpayApiRequestError $e) {
            self::assertStringContainsString('Invalid ID', $e->getMessage());
        }

        try {
            $openpay->customers->getList('not-array');
            self::fail('expected invalid params');
        } catch (OpenpayApiRequestError $e) {
            self::assertStringContainsString('Invalid parameters type', $e->getMessage());
        }
    }

    public function testRefreshDataRejectsNonArray(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada']);
        $method = new \ReflectionMethod(OpenpayApiResourceBase::class, 'refreshData');
        $this->expectException(OpenpayApiError::class);
        $method->invoke($customer, 'nope');
    }

    public function testEmptyUpdateDoesNotHitHttp(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add([]);
        $calls = \count($this->transport->calls);
        $customer->save();
        self::assertCount($calls, $this->transport->calls);
    }

    public function testDerivedGetFetchesWhenNotCached(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cuscccccccccccccccc', 'name' => 'Cara'], \JSON_THROW_ON_ERROR));
        $fetched = $openpay->customers->get('cuscccccccccccccccc');
        self::assertSame('Cara', $fetched->name);
        $again = $openpay->customers->get('cuscccccccccccccccc');
        self::assertSame($fetched, $again);
    }

    public function testSetEmptyStringAndDerivedAssignment(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada']);
        $customer->name = '';
        $list = $customer->cards;
        $customer->cards = $list;
        self::assertSame($list, $customer->cards);
    }
}
