<?php

namespace Tests\Feature\Orders;

use App\Models\Company;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $seller;

    protected Offer $offer;

    protected Company $buyerCompany;

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = Company::factory()->create();
        $this->offer = Offer::factory()->for($this->seller)->published()->create();

        $this->buyerCompany = Company::factory()->create();
        $this->buyer = User::factory()->create();
        $this->buyerCompany->users()->attach($this->buyer, ['is_primary' => true]);
    }

    public function test_an_approved_buyer_can_place_an_order(): void
    {
        $this->assertTrue($this->buyer->can('placeFor', [Order::class, $this->offer]));
    }

    public function test_a_non_primary_member_of_the_buyer_company_can_place_an_order(): void
    {
        $nonPrimaryBuyer = User::factory()->create();
        $this->buyerCompany->users()->attach($nonPrimaryBuyer, ['is_primary' => false]);

        $this->assertTrue($nonPrimaryBuyer->can('placeFor', [Order::class, $this->offer]));
    }

    public function test_a_seller_cannot_order_their_own_offer(): void
    {
        $sellerMember = User::factory()->create();
        $this->seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->assertFalse($sellerMember->can('placeFor', [Order::class, $this->offer]));
    }

    public function test_only_the_seller_can_respond_to_a_pending_order(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);
        $sellerMember = User::factory()->create();
        $this->seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->assertTrue($sellerMember->can('respond', $order));
        $this->assertFalse($this->buyer->can('respond', $order));
    }

    public function test_cannot_respond_to_an_order_that_is_no_longer_pending(): void
    {
        $order = Order::factory()->for($this->offer)->accepted()->create(['buyer_company_id' => $this->buyerCompany->id]);
        $sellerMember = User::factory()->create();
        $this->seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->assertFalse($sellerMember->can('respond', $order));
    }

    public function test_only_the_seller_can_complete_an_accepted_order(): void
    {
        $order = Order::factory()->for($this->offer)->accepted()->create(['buyer_company_id' => $this->buyerCompany->id]);
        $sellerMember = User::factory()->create();
        $this->seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->assertTrue($sellerMember->can('complete', $order));
        $this->assertFalse($this->buyer->can('complete', $order));
    }

    public function test_the_buyer_can_cancel_their_own_pending_order_but_not_an_outsider(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);
        $outsider = User::factory()->create();
        Company::factory()->create()->users()->attach($outsider, ['is_primary' => true]);

        $this->assertTrue($this->buyer->can('cancel', $order));
        $this->assertFalse($outsider->can('cancel', $order));
    }

    public function test_either_party_can_cancel_an_accepted_order(): void
    {
        $order = Order::factory()->for($this->offer)->accepted()->create(['buyer_company_id' => $this->buyerCompany->id]);
        $sellerMember = User::factory()->create();
        $this->seller->users()->attach($sellerMember, ['is_primary' => true]);

        $this->assertTrue($this->buyer->can('cancel', $order));
        $this->assertTrue($sellerMember->can('cancel', $order));
    }

    public function test_a_completed_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->for($this->offer)->completed()->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->assertFalse($this->buyer->can('cancel', $order));
    }
}
