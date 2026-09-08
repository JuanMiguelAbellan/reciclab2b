<?php

namespace Tests\Feature\Orders;

use App\Actions\Conversations\SendMessageAction;
use App\Actions\Conversations\StartConversationAction;
use App\Actions\Offers\CloseOfferAction;
use App\Actions\Orders\AcceptOrderAction;
use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\CompleteOrderAction;
use App\Actions\Orders\PlaceOrderAction;
use App\Actions\Orders\RejectOrderAction;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $seller;

    protected User $sellerMember;

    protected Offer $offer;

    protected Company $buyerCompany;

    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = Company::factory()->create();
        $this->sellerMember = User::factory()->create();
        $this->seller->users()->attach($this->sellerMember, ['is_primary' => true]);

        $this->offer = Offer::factory()->for($this->seller)->published()->create(['quantity_tons' => 100]);

        $this->buyerCompany = Company::factory()->create();
        $this->buyer = User::factory()->create();
        $this->buyerCompany->users()->attach($this->buyer, ['is_primary' => true]);
    }

    public function test_placing_an_order_from_the_offer_page_starts_a_conversation(): void
    {
        $response = $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/pedidos", [
            'quantity_tons' => 20,
        ]);

        $order = Order::first();
        $response->assertRedirect("/pedidos/{$order->id}");
        $this->assertSame(20.0, (float) $order->quantity_tons);
        $this->assertSame((float) $this->offer->price_per_ton, (float) $order->price_per_ton);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNotNull($order->conversation_id);
        $this->assertSame(1, Conversation::count());
    }

    public function test_placing_a_second_order_reuses_the_same_conversation(): void
    {
        $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/pedidos", ['quantity_tons' => 10]);
        $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/pedidos", ['quantity_tons' => 10]);

        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Order::count());
    }

    public function test_cannot_order_more_than_the_available_quantity(): void
    {
        $response = $this->actingAs($this->buyer)->post("/mercado/{$this->offer->id}/pedidos", [
            'quantity_tons' => 150,
        ]);

        $response->assertSessionHasErrors('quantity_tons');
        $this->assertSame(0, Order::count());
    }

    public function test_a_seller_cannot_order_their_own_offer_over_http(): void
    {
        $this->actingAs($this->sellerMember)
            ->post("/mercado/{$this->offer->id}/pedidos", ['quantity_tons' => 5])
            ->assertForbidden();
    }

    public function test_accepting_reduces_available_quantity(): void
    {
        $order = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => $this->buyerCompany->id,
            'quantity_tons' => 40,
        ]);

        (new AcceptOrderAction(new CloseOfferAction(new RejectOrderAction(new SendMessageAction)), new SendMessageAction))->handle($this->sellerMember, $order);

        $this->offer->refresh();
        $this->assertSame(OrderStatus::Accepted, $order->refresh()->status);
        $this->assertSame(60.0, $this->offer->availableQuantity());
        $this->assertSame(OfferStatus::Published, $this->offer->status);
    }

    public function test_accepting_the_last_available_quantity_closes_the_offer(): void
    {
        $order = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => $this->buyerCompany->id,
            'quantity_tons' => 100,
        ]);

        (new AcceptOrderAction(new CloseOfferAction(new RejectOrderAction(new SendMessageAction)), new SendMessageAction))->handle($this->sellerMember, $order);

        $this->offer->refresh();
        $this->assertSame(0.0, $this->offer->availableQuantity());
        $this->assertSame(OfferStatus::Closed, $this->offer->status);
    }

    public function test_a_second_order_cannot_be_accepted_once_the_first_exhausts_the_supply(): void
    {
        $first = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => $this->buyerCompany->id,
            'quantity_tons' => 100,
        ]);
        $second = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => Company::factory()->create()->id,
            'quantity_tons' => 10,
        ]);

        $action = new AcceptOrderAction(new CloseOfferAction(new RejectOrderAction(new SendMessageAction)), new SendMessageAction);
        $action->handle($this->sellerMember, $first);

        $this->expectException(RuntimeException::class);
        $action->handle($this->sellerMember, $second);
    }

    public function test_rejecting_a_pending_order(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);

        (new RejectOrderAction(new SendMessageAction))->handle($this->sellerMember, $order);

        $this->assertSame(OrderStatus::Rejected, $order->refresh()->status);
        $this->assertSame($this->sellerMember->id, $order->responded_by);
    }

    public function test_completing_an_accepted_order(): void
    {
        $order = Order::factory()->for($this->offer)->accepted()->create(['buyer_company_id' => $this->buyerCompany->id]);

        (new CompleteOrderAction(new SendMessageAction))->handle($this->sellerMember, $order);

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_the_buyer_can_cancel_a_pending_order(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);

        (new CancelOrderAction(new SendMessageAction))->handle($this->buyer, $order);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame($this->buyer->id, $order->cancelled_by);
    }

    public function test_cancelling_an_accepted_order_that_closed_the_offer_does_not_reopen_it(): void
    {
        $order = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => $this->buyerCompany->id,
            'quantity_tons' => 100,
        ]);
        (new AcceptOrderAction(new CloseOfferAction(new RejectOrderAction(new SendMessageAction)), new SendMessageAction))->handle($this->sellerMember, $order);
        $this->offer->refresh();
        $this->assertSame(OfferStatus::Closed, $this->offer->status);

        (new CancelOrderAction(new SendMessageAction))->handle($this->sellerMember, $order);

        $this->offer->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(OfferStatus::Closed, $this->offer->status);
    }

    public function test_a_completed_order_cannot_be_cancelled_via_the_action(): void
    {
        $order = Order::factory()->for($this->offer)->completed()->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->expectException(RuntimeException::class);
        (new CancelOrderAction(new SendMessageAction))->handle($this->buyer, $order);
    }

    public function test_placing_an_order_from_within_a_conversation_links_it(): void
    {
        $conversation = Conversation::factory()->create([
            'offer_id' => $this->offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);

        $response = $this->actingAs($this->buyer)->post("/mensajes/{$conversation->id}/pedidos", [
            'quantity_tons' => 15,
        ]);

        $order = Order::first();
        $response->assertRedirect("/pedidos/{$order->id}");
        $this->assertSame($conversation->id, $order->conversation_id);
    }

    public function test_the_index_and_show_pages_render(): void
    {
        $action = new PlaceOrderAction(new StartConversationAction);
        $order = $action->handle($this->buyer, $this->offer, 10.0);

        $this->actingAs($this->buyer)->get('/pedidos')->assertOk();
        $this->actingAs($this->buyer)->get("/pedidos/{$order->id}")->assertOk();
        $this->actingAs($this->sellerMember)->get("/pedidos/{$order->id}")->assertOk();
    }

    public function test_an_outsider_cannot_view_someone_elses_order(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);
        $outsider = User::factory()->create();
        Company::factory()->create()->users()->attach($outsider, ['is_primary' => true]);

        $this->actingAs($outsider)->get("/pedidos/{$order->id}")->assertForbidden();
    }

    public function test_the_seller_can_accept_a_pending_order_over_http(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->actingAs($this->sellerMember)
            ->post("/pedidos/{$order->id}/aceptar")
            ->assertRedirect();

        $this->assertSame(OrderStatus::Accepted, $order->refresh()->status);
    }

    public function test_the_buyer_cannot_accept_their_own_order(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->actingAs($this->buyer)
            ->post("/pedidos/{$order->id}/aceptar")
            ->assertForbidden();
    }

    public function test_the_seller_can_reject_a_pending_order_over_http(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->actingAs($this->sellerMember)
            ->post("/pedidos/{$order->id}/rechazar")
            ->assertRedirect();

        $this->assertSame(OrderStatus::Rejected, $order->refresh()->status);
    }

    public function test_the_buyer_can_cancel_their_pending_order_over_http(): void
    {
        $order = Order::factory()->for($this->offer)->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->actingAs($this->buyer)
            ->post("/pedidos/{$order->id}/cancelar")
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    public function test_the_seller_can_complete_an_accepted_order_over_http(): void
    {
        $order = Order::factory()->for($this->offer)->accepted()->create(['buyer_company_id' => $this->buyerCompany->id]);

        $this->actingAs($this->sellerMember)
            ->post("/pedidos/{$order->id}/completar")
            ->assertRedirect();

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
    }

    public function test_accepting_an_order_posts_a_system_message_in_its_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'offer_id' => $this->offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);
        $order = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => $this->buyerCompany->id,
            'conversation_id' => $conversation->id,
        ]);

        $this->actingAs($this->sellerMember)->post("/pedidos/{$order->id}/aceptar");

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->sellerMember->id,
        ]);
    }

    public function test_cancelling_an_order_posts_a_system_message_in_its_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'offer_id' => $this->offer->id,
            'buyer_company_id' => $this->buyerCompany->id,
        ]);
        $order = Order::factory()->for($this->offer)->create([
            'buyer_company_id' => $this->buyerCompany->id,
            'conversation_id' => $conversation->id,
        ]);

        $this->actingAs($this->buyer)->post("/pedidos/{$order->id}/cancelar");

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
        ]);
    }
}
