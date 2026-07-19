<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\User;

it('renders title, canonical and product structured data for an active ad', function (): void {
    $ad = Ad::factory()->create([
        'title' => 'Rower gorski Kross',
        'location' => 'Warszawa',
        'price' => 1999.99,
        'slug' => 'rower-gorski-kross-warszawa',
    ]);
    AdImage::factory()->for($ad)->create(['path' => 'ads/rower.jpg']);

    $response = $this->get('/ogloszenie/rower-gorski-kross-warszawa')->assertOk();

    $response->assertSee('<title>Rower gorski Kross – Warszawa | '.config('seo.site_name').'</title>', false);
    $response->assertSee('rel="canonical" href="'.route('ads.show', ['slug' => $ad->slug]).'"', false);
    $response->assertSee('name="robots" content="index, follow"', false);
    $response->assertSee('property="og:type" content="product"', false);
    $response->assertSee('"@type":"Product"', false);
    $response->assertSee('"priceCurrency":"PLN"', false);
    $response->assertSee('"price":"1999.99"', false);
});

it('omits the offer node when the ad has no price', function (): void {
    Ad::factory()->create(['slug' => 'bez-ceny', 'price' => null]);

    $this->get('/ogloszenie/bez-ceny')
        ->assertOk()
        ->assertSee('"@type":"Product"', false)
        ->assertDontSee('"@type":"Offer"', false);
});

it('escapes structured data so an ad description cannot inject a script', function (): void {
    Ad::factory()->create([
        'slug' => 'zlosliwe',
        'description' => 'Uwaga </script><script>alert(1)</script> koniec.',
    ]);

    $this->get('/ogloszenie/zlosliwe')
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)', false);
});

it('answers 410 Gone for an expired ad instead of a soft 404', function (): void {
    Ad::factory()->expired()->create(['slug' => 'wygasle']);

    $this->get('/ogloszenie/wygasle')->assertStatus(410);
});

it('answers 410 Gone for a deleted ad instead of rendering it again by slug', function (): void {
    Ad::factory()->deleted()->create(['slug' => 'usuniete']);

    $this->get('/ogloszenie/usuniete')
        ->assertStatus(410)
        ->assertDontSee('property="og:type" content="product"', false);
});

it('treats an ad past its expiry as gone even before the hourly sweep runs', function (): void {
    // `ads:expire` chodzi raz na godzinę, więc status jest jeszcze `active`.
    Ad::factory()->lapsed()->create(['slug' => 'przeterminowane']);

    $this->get('/ogloszenie/przeterminowane')->assertStatus(410);
});

it('answers 404 for an ad awaiting moderation, which was never public', function (): void {
    Ad::factory()->pending()->create(['slug' => 'oczekujace']);

    $this->get('/ogloszenie/oczekujace')->assertNotFound();
});

it('lets the author preview their pending ad, but keeps it out of the index', function (): void {
    $author = User::factory()->create();
    Ad::factory()->for($author)->pending()->create(['slug' => 'moje-oczekujace']);

    $this->actingAs($author)
        ->get('/ogloszenie/moje-oczekujace')
        ->assertOk()
        ->assertSee('name="robots" content="noindex, follow"', false);
});

it('answers 404 for a slug that never existed', function (): void {
    $this->get('/ogloszenie/nigdy-nie-istnialo')->assertNotFound();
});

it('answers 404 with the SPA shell rather than 200 for an unknown url', function (): void {
    $this->get('/kompletnie-zmyslony-adres')
        ->assertNotFound()
        ->assertSee('id="app"', false);
});
