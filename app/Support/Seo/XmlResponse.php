<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Http\Response;

/**
 * Deklaracja XML nie może stać w szablonie Blade. Blade parsuje plik przez
 * `token_get_all()`, a przy włączonym `short_open_tag` PHP widzi w `<?xml`
 * znacznik otwierający i wysypuje kompilację szablonu.
 */
final class XmlResponse
{
    private const string DECLARATION = '<?xml version="1.0" encoding="UTF-8"?>';

    public function make(string $body, string $contentType): Response
    {
        return response(
            self::DECLARATION."\n".ltrim($body),
            Response::HTTP_OK,
            ['Content-Type' => $contentType.'; charset=UTF-8'],
        );
    }
}
