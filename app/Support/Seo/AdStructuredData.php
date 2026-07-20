<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Enums\AdCondition;
use App\Models\Ad;
use App\Models\AdImage;
use Illuminate\Support\Facades\Config;

/**
 * Ogłoszenie z marketplace'u bez własnego systemu ocen nie powinno udawać
 * produktu z recenzjami. Product bez `review` / `aggregateRating` generował w
 * Search Console stałe ostrzeżenia, więc opisujemy stronę neutralnie:
 * przedmiot jako Thing, a gdy jest cena — ofertę jako Offer.
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

        $item = [
            '@context' => 'https://schema.org',
            '@type' => 'Thing',
            'name' => $ad->title,
            'description' => $this->text->description($ad),
            'url' => $url,
            'category' => $ad->category->name,
        ];

        $images = $ad->images->map(fn (AdImage $image): string => $image->url())->all();

        if ($images !== []) {
            $item['image'] = $images;
        }

        if ($ad->price === null) {
            return $item;
        }

        return $this->offer($ad, $url, $item);
    }

    /**
     * @return array<string, mixed>
     */
    private function offer(Ad $ad, string $url, array $item): array
    {
        $offer = [
            '@type' => 'Offer',
            'url' => $url,
            'itemOffered' => $item,
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
