<?php

declare(strict_types=1);

use App\Search\Contracts\AdSearchEngine;
use App\Search\Database\DatabaseAdSearchEngine;
use Tests\Fixtures\RecordingAdSearchEngine;

it('binds the database engine by default', function (): void {
    expect(app(AdSearchEngine::class))->toBeInstanceOf(DatabaseAdSearchEngine::class);
});

it('resolves the engine named by the search driver config', function (): void {
    // Symulacja podmiany silnika bez dotykania kodu — tak samo wejdzie kiedyś
    // sterownik 'elasticsearch'.
    config()->set('search.drivers.recording', RecordingAdSearchEngine::class);
    config()->set('search.driver', 'recording');

    expect(app(AdSearchEngine::class))->toBeInstanceOf(RecordingAdSearchEngine::class);
});

it('falls back to the database engine for an unknown driver', function (): void {
    config()->set('search.driver', 'nonexistent');

    expect(app(AdSearchEngine::class))->toBeInstanceOf(DatabaseAdSearchEngine::class);
});

it('runs the public listing through the AdSearchEngine contract', function (): void {
    $engine = new RecordingAdSearchEngine;
    app()->instance(AdSearchEngine::class, $engine);

    $this->getJson('/api/v1/ads?q=laptop&sort=newest')->assertOk();

    // Kontroler oddał żądanie kontraktowi, przekazując znormalizowane kryteria —
    // ten sam kontrakt dostanie później silnik Elasticsearch.
    expect($engine->lastCriteria)->toMatchArray(['q' => 'laptop', 'sort' => 'newest']);
});
