<?php

namespace App\Services\Stats;

use App\Models\Offer;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talks to the reciclab2b-stats microservice (separate repo, own database).
 *
 * Every call here is best-effort: if the stats service is down, slow, or
 * returns an error, that must never block a producer publishing an offer or
 * a seller completing an order. Webhook calls log a warning and swallow the
 * failure; the price suggestion lookup returns null so the UI can hide it.
 */
class StatsWebhookClient
{
    private const TIMEOUT_SECONDS = 2;

    public function offerPublished(Offer $offer): void
    {
        $this->sendOfferEvent($offer, 'published');
    }

    public function offerClosed(Offer $offer): void
    {
        $this->sendOfferEvent($offer, 'closed');
    }

    public function orderCompleted(Order $order): void
    {
        if (! $this->configured()) {
            return;
        }

        try {
            $response = $this->client()->post($this->url('/webhooks/order-events'), [
                'external_order_id' => $order->id,
                'external_offer_id' => $order->offer_id,
                'material' => $order->offer->material->value,
                'quantity_tons' => (float) $order->quantity_tons,
                'price_per_ton' => (float) $order->price_per_ton,
                'generation_year' => $order->offer->generation_year,
                'occurred_at' => now()->toIso8601String(),
            ]);

            if ($response->failed()) {
                Log::warning('reciclab2b-stats: order-events webhook failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('reciclab2b-stats: order-events webhook threw', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{suggested_price_per_ton: float|null, sample_size: int, source: string}|null
     *         null means "couldn't get a suggestion" (service down, no config, bad response) —
     *         the caller treats that the same as "no suggestion available".
     */
    public function priceSuggestion(string $material, int $generationYear): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = $this->client()->get($this->url('/api/price-suggestions'), [
                'material' => $material,
                'generation_year' => $generationYear,
            ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::warning('reciclab2b-stats: price-suggestions lookup threw', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function sendOfferEvent(Offer $offer, string $eventType): void
    {
        if (! $this->configured()) {
            return;
        }

        try {
            $response = $this->client()->post($this->url('/webhooks/offer-events'), [
                'external_offer_id' => $offer->id,
                'event_type' => $eventType,
                'material' => $offer->material->value,
                'quantity_tons' => (float) $offer->quantity_tons,
                'price_per_ton' => (float) $offer->price_per_ton,
                'generation_year' => $offer->generation_year,
                'province' => $offer->province,
                'occurred_at' => now()->toIso8601String(),
            ]);

            if ($response->failed()) {
                Log::warning('reciclab2b-stats: offer-events webhook failed', [
                    'offer_id' => $offer->id,
                    'event_type' => $eventType,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('reciclab2b-stats: offer-events webhook threw', [
                'offer_id' => $offer->id,
                'event_type' => $eventType,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function configured(): bool
    {
        return filled(config('services.reciclab2b_stats.url'));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.reciclab2b_stats.url'), '/').$path;
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['X-Api-Key' => config('services.reciclab2b_stats.api_key')])
            ->timeout(self::TIMEOUT_SECONDS);
    }
}
