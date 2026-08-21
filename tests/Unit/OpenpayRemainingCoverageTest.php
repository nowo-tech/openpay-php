<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApi;
use Openpay\Data\OpenpayApiDerivedResource;
use Openpay\Data\OpenpayApiResourceBase;
use Openpay\Resources\OpenpayChargeList;
use Openpay\Resources\OpenpayCustomer;
use Openpay\Resources\OpenpaySubscription;
use PHPUnit\Framework\TestCase;

final class OpenpayRemainingCoverageTest extends TestCase
{
    private FakeOpenpayHttpTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        Openpay::reset();
        $this->transport = new FakeOpenpayHttpTransport();
        Openpay::setHttpTransport($this->transport);
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
    }

    protected function tearDown(): void
    {
        Openpay::reset();
        parent::tearDown();
    }

    public function testGetInstanceStringIdAndRetrieveWithoutParentProps(): void
    {
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada'], \JSON_THROW_ON_ERROR));
        $method = new \ReflectionMethod(OpenpayApiResourceBase::class, 'getInstance');
        $customer = $method->invoke(null, OpenpayCustomer::class, 'cusaaaaaaaaaaaaaaaa');
        self::assertInstanceOf(OpenpayCustomer::class, $customer);

        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada'], \JSON_THROW_ON_ERROR));
        $retrieve = new \ReflectionMethod(OpenpayApiResourceBase::class, '_retrieve');
        $loaded = $retrieve->invoke($customer, OpenpayCustomer::class, 'cusaaaaaaaaaaaaaaaa', null);
        self::assertSame('Ada', $loaded->name);
    }

    public function testProcessAttributeListAndOpenpayPrefixedName(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada']);

        $this->transport->enqueue(json_encode(['id' => 'cardaaaaaaaaaaaaaaaa', 'brand' => 'visa'], \JSON_THROW_ON_ERROR));
        $process = new \ReflectionMethod(OpenpayApiResourceBase::class, 'processAttribute');
        $list = $process->invoke($customer, 'card', [['id' => 'cardaaaaaaaaaaaaaaaa', 'brand' => 'visa']]);
        self::assertInstanceOf(OpenpayApiDerivedResource::class, $list);

        $name = new \ReflectionMethod(OpenpayApiResourceBase::class, 'getResourceName');
        self::assertSame('OpenpayCard', $name->invoke($customer, 'OpenpayCard'));
    }

    public function testRegisterInParentWithoutParent(): void
    {
        $ref = new \ReflectionClass(OpenpayCustomer::class);
        $customer = $ref->newInstanceWithoutConstructor();
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);
        $ctor->invoke($customer, OpenpayCustomer::class, ['id' => 'cusaaaaaaaaaaaaaaaa']);
        $method = new \ReflectionMethod(OpenpayApiResourceBase::class, 'registerInParent');
        $method->invoke($customer, $customer);
        $this->addToAssertionCount(1);
    }

    public function testRefreshRemovesStaleKeysAndNoSerializableGet(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada', 'email' => 'a@b.c'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada', 'email' => 'a@b.c']);

        $noSer = new \ReflectionProperty(OpenpayApiResourceBase::class, 'noSerializableData');
        $noSer->setValue($customer, ['ghost' => '1']);
        $refresh = new \ReflectionMethod(OpenpayApiResourceBase::class, 'refreshData');
        $refresh->invoke($customer, ['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada']);
        self::assertNull($customer->email);
        self::assertSame('1', $customer->ghost);
    }

    public function testListResourceUrlNameAndMerchantInfo(): void
    {
        $ref = new \ReflectionClass(OpenpayChargeList::class);
        $list = $ref->newInstanceWithoutConstructor();
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);
        $ctor->invoke($list, 'OpenpayChargeList', []);
        $url = new \ReflectionMethod(OpenpayApiResourceBase::class, 'getResourceUrlName');
        self::assertSame('charges', $url->invoke($list, true));

        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'aaaaaaaaaaaaaaaaaaaa', 'name' => 'm'], \JSON_THROW_ON_ERROR));
        $info = new \ReflectionMethod(OpenpayApi::class, 'getMerchantInfo');
        self::assertSame('m', $info->invoke($openpay)->name);
    }

    public function testAddResourceStringIdSubscriptionParentSetAndCustomerDelete(): void
    {
        $openpay = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $this->transport->enqueue(json_encode(['id' => 'cusaaaaaaaaaaaaaaaa', 'name' => 'Ada'], \JSON_THROW_ON_ERROR));
        $customer = $openpay->customers->add(['name' => 'Ada']);
        $this->transport->enqueue('');
        $customer->delete();

        $this->transport->enqueue(json_encode(['id' => 'subaaaaaaaaaaaaaaaaa', 'plan_id' => 'pln1'], \JSON_THROW_ON_ERROR));
        $sub = (new \ReflectionMethod(OpenpayApiResourceBase::class, 'getInstance'))
            ->invoke(null, OpenpaySubscription::class, ['id' => 'subaaaaaaaaaaaaaaaaa']);
        $sub->status = 'active';
        $add = new \ReflectionMethod(OpenpayApiDerivedResource::class, 'addResource');
        $add->invoke($openpay->customers, $sub, 'SUBAAAAAAAAAA');
        $get = new \ReflectionMethod(OpenpayApiDerivedResource::class, 'getResource');
        self::assertSame($sub, $get->invoke($openpay->customers, 'subaaaaaaaaaa'));
        $remove = new \ReflectionMethod(OpenpayApiDerivedResource::class, 'removeResource');
        $remove->invoke($openpay->customers, 'missing');
        $remove->invoke($openpay->customers, 'subaaaaaaaaaa');
    }
}
