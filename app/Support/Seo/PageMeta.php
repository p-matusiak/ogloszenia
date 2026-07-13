<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * To, co trafia do `<head>` powłoki SPA. Serwer musi to policzyć sam, bo
 * crawlery społecznościowe (Facebook, X, WhatsApp, Slack) nie wykonują
 * JavaScriptu i widzą wyłącznie pierwszą odpowiedź HTML.
 */
final class PageMeta
{
    public const string TYPE_WEBSITE = 'website';

    public const string TYPE_PRODUCT = 'product';

    public ?string $imageUrl = null;

    /** Gotowy JSON dla `<script type="application/ld+json">`. */
    public ?string $structuredData = null;

    public string $openGraphType = self::TYPE_WEBSITE;

    /** Kanał RSS węższy niż globalny — dziś tylko na stronie kategorii. */
    public ?string $feedUrl = null;

    public ?string $feedTitle = null;

    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly bool $indexable = true,
    ) {}

    public function withImage(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function withOpenGraphType(string $openGraphType): self
    {
        $this->openGraphType = $openGraphType;

        return $this;
    }

    public function withFeed(string $feedUrl, string $feedTitle): self
    {
        $this->feedUrl = $feedUrl;
        $this->feedTitle = $feedTitle;

        return $this;
    }

    /**
     * Kodowanie ucieka `<`, `>` i `&`, więc treść ogłoszenia nie jest w stanie
     * zamknąć taga `</script>` i wstrzyknąć własnego kodu na stronę.
     *
     * @param  array<string, mixed>  $data
     */
    public function withStructuredData(array $data): self
    {
        $this->structuredData = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP,
        );

        return $this;
    }
}
