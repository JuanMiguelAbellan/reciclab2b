import { useEffect, useState } from 'react';

interface PriceSuggestion {
    material: string;
    generation_year: number | null;
    suggested_price_per_ton: number | null;
    sample_size: number;
    source: 'completed_orders' | 'published_offers' | 'insufficient_data';
    lookback_days: number;
}

const SOURCE_LABEL: Record<PriceSuggestion['source'], string> = {
    completed_orders: 'pedidos completados recientes',
    published_offers: 'ofertas publicadas recientes',
    insufficient_data: '',
};

/**
 * Fetches a suggested price from the reciclab2b-stats service as the producer
 * fills in material + generation year. A manual fetch (not an Inertia reload)
 * because this only needs to react to two fields changing, debounced, without
 * reloading the whole page — see the controller docblock for why this is the
 * justified exception to the project's "no manual fetch" rule.
 */
export default function PriceSuggestionHint({ material, generationYear }: { material: string; generationYear: string }) {
    const [suggestion, setSuggestion] = useState<PriceSuggestion | null>(null);

    useEffect(() => {
        const year = Number(generationYear);
        if (!material || !year || Number.isNaN(year)) {
            setSuggestion(null);
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(() => {
            fetch(`/ofertas/sugerencia-precio?material=${encodeURIComponent(material)}&generation_year=${year}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((body: { suggestion: PriceSuggestion | null } | null) => setSuggestion(body?.suggestion ?? null))
                .catch(() => setSuggestion(null));
        }, 400);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [material, generationYear]);

    if (!suggestion || suggestion.suggested_price_per_ton === null) {
        return null;
    }

    return (
        <p className="mt-1 text-xs text-neutral-500">
            Precio sugerido: {suggestion.suggested_price_per_ton.toFixed(2)} €/t, según {suggestion.sample_size}{' '}
            {SOURCE_LABEL[suggestion.source]}.
        </p>
    );
}
