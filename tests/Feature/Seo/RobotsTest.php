<?php

declare(strict_types=1);

it('points crawlers at the sitemap and away from the private pages', function (): void {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('text/plain; charset=UTF-8');

    $response->assertSee('User-agent: *', false);
    $response->assertSee('Sitemap: '.route('sitemap'), false);
    $response->assertSee('Disallow: /admin', false);
    $response->assertSee('Disallow: /moje-ogloszenia', false);
    // Wyniki wyszukiwania to treść wtórna: budżet indeksowania ma iść na ogłoszenia.
    $response->assertSee('Disallow: /*?*q=', false);
});
