<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Ad;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Teksty, które ogłoszenie pokazuje na zewnątrz aplikacji: w wynikach
 * wyszukiwania, w podglądzie linku na czacie i w kanale RSS.
 */
final class AdSeoText
{
    /** Google ucina opis w okolicach 160 znaków. */
    private const int META_DESCRIPTION_LENGTH = 160;

    private const int FEED_DESCRIPTION_LENGTH = 280;

    private const string FREE_LABEL = 'Za darmo';

    private const string NO_PRICE_LABEL = 'Cena do uzgodnienia';

    /**
     * Lokalizacja w tytule, bo zapytania o ogłoszenia są niemal zawsze lokalne
     * („rower górski Warszawa”).
     */
    public function title(Ad $ad): string
    {
        return $ad->location === null || $ad->location === ''
            ? $ad->title
            : $ad->title.' – '.$ad->location;
    }

    public function description(Ad $ad): string
    {
        return $this->summary($ad, self::META_DESCRIPTION_LENGTH);
    }

    public function feedDescription(Ad $ad): string
    {
        return $this->summary($ad, self::FEED_DESCRIPTION_LENGTH);
    }

    /**
     * `price` jest castowane na `decimal:2`, czyli przychodzi jako string —
     * porównanie do zera musi iść po wartości, nie po tekście („0.00”).
     */
    public function price(?string $price, bool $isNegotiable): string
    {
        if ($price === null) {
            return self::NO_PRICE_LABEL;
        }

        $value = (float) $price;

        if ($value === 0.0) {
            return self::FREE_LABEL;
        }

        $formatted = number_format($value, 2, ',', "\u{a0}").' '.Config::string('ads.currency_symbol');

        return $isNegotiable ? $formatted.' (do negocjacji)' : $formatted;
    }

    /**
     * Cena i lokalizacja przed opisem: to one decydują, czy ktoś kliknie wynik.
     */
    private function summary(Ad $ad, int $length): string
    {
        $lead = implode(' · ', array_filter([
            $this->price($ad->price, $ad->is_negotiable),
            $ad->location,
        ]));

        return Str::limit($lead.' — '.$this->plain($ad->description), $length);
    }

    private function plain(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
    }
}
