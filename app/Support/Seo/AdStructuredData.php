<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Enums\AdCondition;
use App\Models\Ad;
use App\Models\AdImage;
use Illuminate\Support\Facades\Config;

/**
 * schema.org/Product z zagnieżdżonym Offer — dokładnie ten kształt Google
 * zamienia w wynik rozszerzony z ceną, dostępnością i zdjęciem.
 *
 * Wymaga załadowanych relacji `category` i `images`.
 */
final class AdStructuredData
{
    public function __construct(
        private readonly AdSeoText $text,
        private readonly SiteUrl $siteUrl,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Ad $ad): array
    {
        $url = $this->siteUrl->route('ads.show', ['slug' => $ad->slug]);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $ad->title,
            'description' => $this->text->description($ad),
            'url' => $url,
            'category' => $ad->category->name,
        ];

        $images = $ad->images->map(fn (AdImage $image): string => $image->url())->all();

        if ($images !== []) {
            $data['image'] = $images;
        }

        // Bez ceny nie ma oferty w rozumieniu schema.org. Zostaje sam Product,
        // który nadal opisuje przedmiot — tylko bez wyniku rozszerzonego.
        if ($ad->price !== null) {
            $data['offers'] = $this->offer($ad, $url);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function offer(Ad $ad, string $url): array
    {
        $offer = [
            '@type' => 'Offer',
            'url' => $url,
            // Kropka jako separator dziesiętny i brak separatora tysięcy:
            // schema.org czyta tę wartość maszynowo, nie po polsku.
            'price' => number_format((float) $ad->price, 2, '.', ''),
            'priceCurrency' => Config::string('ads.currency'),
            'availability' => 'https://schema.org/InStock',
        ];

        if ($ad->condition !== null) {
            $offer['itemCondition'] = $this->condition($ad->condition);
        }

        if ($ad->expires_at !== null) {
            $offer['priceValidUntil'] = $ad->expires_at->toDateString();
        }

        return $offer;
    }

    private function condition(AdCondition $condition): string
    {
        return match ($condition) {
            AdCondition::New => 'https://schema.org/NewCondition',
            AdCondition::Used => 'https://schema.org/UsedCondition',
            AdCondition::Damaged => 'https://schema.org/DamagedCondition',
        };
    }
}
