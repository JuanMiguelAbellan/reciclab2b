<?php

namespace Tests\Unit\Services\Stats;

use App\Enums\MaterialType;
use App\Models\Offer;
use App\Models\Order;
use App\Services\Stats\StatsWebhookClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StatsWebhookClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reciclab2b_stats.url' => 'http://stats.test',
            'services.reciclab2b_stats.api_key' => 'secret-key',
        ]);
    }

    public function test_it_sends_nothing_when_the_service_is_not_configured(): void
    {
        config(['services.reciclab2b_stats.url' => null]);
        Http::fake();

        $offer = Offer::factory()->create(['material' => MaterialType::Cardboard]);

        (new StatsWebhookClient)->offerPublished($offer);

        Http::assertNothingSent();
    }

    public function test_offer_published_sends_the_expected_payload_and_api_key(): void
    {
        Http::fake(['stats.test/*' => Http::response(null, 201)]);

        $offer = Offer::factory()->create([
            'material' => MaterialType::Cardboard,
            'quantity_tons' => 12.5,
            'price_per_ton' => 185.0,
            'generation_year' => 2026,
            'province' => 'Huesca',
        ]);

        (new StatsWebhookClient)->offerPublished($offer);

        Http::assertSent(function ($request) use ($offer) {
            return $request->url() === 'http://stats.test/webhooks/offer-events'
                && $request->hasHeader('X-Api-Key', 'secret-key')
                && $request['external_offer_id'] === $offer->id
                && $request['event_type'] === 'published'
                && $request['material'] === 'carton'
                && $request['quantity_tons'] === 12.5
                && $request['price_per_ton'] === 185.0
                && $request['generation_year'] === 2026
                && $request['province'] === 'Huesca';
        });
    }

    public function test_offer_closed_sends_event_type_closed(): void
    {
        Http::fake(['stats.test/*' => Http::response(null, 201)]);

        $offer = Offer::factory()->create(['material' => MaterialType::Plastic]);

        (new StatsWebhookClient)->offerClosed($offer);

        Http::assertSent(fn ($request) => $request['event_type'] === 'closed' && $request['material'] === 'plastico');
    }

    public function test_order_completed_sends_the_offers_material_and_generation_year(): void
    {
        Http::fake(['stats.test/*' => Http::response(null, 201)]);

        $offer = Offer::factory()->create(['material' => MaterialType::Cardboard, 'generation_year' => 2025]);
        $order = Order::factory()->for($offer)->create(['quantity_tons' => 5, 'price_per_ton' => 190]);

        (new StatsWebhookClient)->orderCompleted($order);

        Http::assertSent(function ($request) use ($order, $offer) {
            return $request->url() === 'http://stats.test/webhooks/order-events'
                && $request['external_order_id'] === $order->id
                && $request['external_offer_id'] === $offer->id
                && $request['material'] === 'carton'
                && $request['generation_year'] === 2025;
        });
    }

    public function test_a_failed_webhook_response_never_throws(): void
    {
        Http::fake(['stats.test/*' => Http::response(['error' => 'boom'], 500)]);

        $offer = Offer::factory()->create();

        (new StatsWebhookClient)->offerPublished($offer);

        $this->assertTrue(true); // reaching here means it didn't throw
    }

    public function test_a_connection_failure_never_throws(): void
    {
        Http::fake(function () {
            throw new ConnectionException('stats service unreachable');
        });

        $offer = Offer::factory()->create();

        (new StatsWebhookClient)->offerPublished($offer);

        $this->assertTrue(true);
    }

    public function test_price_suggestion_returns_the_parsed_response(): void
    {
        Http::fake(['stats.test/*' => Http::response([
            'material' => 'carton',
            'generation_year' => 2026,
            'suggested_price_per_ton' => 182.5,
            'sample_size' => 4,
            'source' => 'completed_orders',
            'lookback_days' => 90,
        ], 200)]);

        $suggestion = (new StatsWebhookClient)->priceSuggestion('carton', 2026);

        $this->assertSame(182.5, $suggestion['suggested_price_per_ton']);
        $this->assertSame('completed_orders', $suggestion['source']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/price-suggestions')
            && $request['material'] === 'carton'
            && $request['generation_year'] === 2026
            && $request->hasHeader('X-Api-Key', 'secret-key'));
    }

    public function test_price_suggestion_returns_null_when_the_service_fails(): void
    {
        Http::fake(['stats.test/*' => Http::response(null, 500)]);

        $this->assertNull((new StatsWebhookClient)->priceSuggestion('carton', 2026));
    }

    public function test_price_suggestion_returns_null_when_not_configured(): void
    {
        config(['services.reciclab2b_stats.url' => null]);
        Http::fake();

        $this->assertNull((new StatsWebhookClient)->priceSuggestion('carton', 2026));
        Http::assertNothingSent();
    }
}
