<?php

namespace App\Actions\Assistant;

use App\Enums\SaleItemType;
use App\Models\ProductPresentation;
use App\Models\Scopes\Active;
use Illuminate\Database\Eloquent\Builder;

class ResolveSaleableItemMatches
{
    /**
     * Filler words that shouldn't count as evidence for/against a match — a search like "agua
     * mineral de 2 litros" has to match "Agua Mineral 2L" even though "de" and "litros" don't
     * appear literally in the product name.
     *
     * @var array<int, string>
     */
    private const STOPWORDS = [
        'de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas', 'y', 'con', 'para', 'a', 'en',
        'the', 'of', 'for', 'and', 'with', 'an', 'in',
    ];

    /**
     * Resolve free-text item requests (as mentioned in chat) into concrete product presentations.
     *
     * Matching is score-based, not an exact/all-words match: it fetches every presentation that
     * matches *any* significant word in the request, then picks whichever one matches the *most*
     * words. This is what lets a natural sentence like "agua mineral de 2 litros" resolve to a
     * product literally named "Agua Mineral 2L" — requiring every word to match verbatim would
     * reject it outright over filler words and unit-notation differences.
     *
     * Only plain products are supported: composite products have no stored price and promotions'
     * price is optional, so neither can be priced reliably without a human picking a value — the
     * same limitation the manual sale/order/quote creation screens have today.
     *
     * @param  array<int, array{search: string, quantity: float|int|string}>  $requestedItems
     * @return array<int, array{
     *     status: string,
     *     query: string,
     *     quantity: float,
     *     item_type: string|null,
     *     saleable_id: string|null,
     *     description: string|null,
     *     unit_price: float|null,
     *     candidates: array<int, string>,
     * }>
     */
    public function execute(array $requestedItems): array
    {
        return array_map(fn (array $requested) => $this->resolveOne($requested), $requestedItems);
    }

    /**
     * @param  array{search: string, quantity: float|int|string}  $requested
     * @return array{
     *     status: string,
     *     query: string,
     *     quantity: float,
     *     item_type: string|null,
     *     saleable_id: string|null,
     *     description: string|null,
     *     unit_price: float|null,
     *     candidates: array<int, string>,
     * }
     */
    private function resolveOne(array $requested): array
    {
        $search = trim((string) ($requested['search'] ?? ''));
        $quantity = (float) ($requested['quantity'] ?? 0);

        $terms = $this->significantTerms($search);

        if ($terms === []) {
            return $this->unresolved('not_found', $search, $quantity);
        }

        // Presentations have no searchable "name" (only a quantity + unit type, see
        // Presentation::display()) so the SQL net is cast on product name/barcode only; the
        // presentation's display text still contributes to the score below, in PHP.
        $candidates = ProductPresentation::query()
            ->withScopes(new Active)
            ->whereHas('product', function (Builder $q) use ($terms) {
                $q->withScopes(new Active)->where(function (Builder $query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->orWhere('name', 'like', "%{$term}%")
                            ->orWhereHas('barcodes', fn (Builder $b) => $b->where('barcode', 'like', "%{$term}%"));
                    }
                });
            })
            ->with(['product', 'presentation.presentationType'])
            ->limit(30)
            ->get();

        if ($candidates->isEmpty()) {
            return $this->unresolved('not_found', $search, $quantity);
        }

        $scored = $candidates
            ->map(fn (ProductPresentation $pp) => ['presentation' => $pp, 'score' => $this->score($pp, $terms)])
            ->filter(fn (array $c) => $c['score'] > 0)
            ->values();

        if ($scored->isEmpty()) {
            return $this->unresolved('not_found', $search, $quantity);
        }

        $maxScore = $scored->max('score');
        $best = $scored->filter(fn (array $c) => $c['score'] === $maxScore)->values();

        if ($best->count() > 1) {
            return $this->unresolved(
                'ambiguous',
                $search,
                $quantity,
                $best->map(fn (array $c) => "{$c['presentation']->product?->name} ({$c['presentation']->presentation?->display})")->all(),
            );
        }

        $pp = $best->first()['presentation'];

        return [
            'status' => 'resolved',
            'query' => $search,
            'quantity' => $quantity,
            'item_type' => SaleItemType::Product->value,
            'saleable_id' => $pp->uuid,
            'description' => "{$pp->product?->name} ({$pp->presentation?->display})",
            'unit_price' => (float) $pp->price,
            'candidates' => [],
        ];
    }

    /** @return array<int, string> */
    private function significantTerms(string $search): array
    {
        $allTerms = array_values(array_filter(preg_split('/\s+/', mb_strtolower($search)) ?: []));
        $significant = array_values(array_diff($allTerms, self::STOPWORDS));

        // If the search was made up entirely of stopwords (unlikely, but possible), fall back to
        // the full term list rather than matching nothing.
        return $significant !== [] ? $significant : $allTerms;
    }

    /** @param  array<int, string>  $terms */
    private function score(ProductPresentation $pp, array $terms): int
    {
        $haystack = mb_strtolower(($pp->product?->name ?? '').' '.($pp->presentation?->display ?? ''));

        $score = 0;
        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param  array<int, string>  $candidates
     * @return array{
     *     status: string,
     *     query: string,
     *     quantity: float,
     *     item_type: string|null,
     *     saleable_id: string|null,
     *     description: string|null,
     *     unit_price: float|null,
     *     candidates: array<int, string>,
     * }
     */
    private function unresolved(string $status, string $search, float $quantity, array $candidates = []): array
    {
        return [
            'status' => $status,
            'query' => $search,
            'quantity' => $quantity,
            'item_type' => null,
            'saleable_id' => null,
            'description' => null,
            'unit_price' => null,
            'candidates' => $candidates,
        ];
    }
}
