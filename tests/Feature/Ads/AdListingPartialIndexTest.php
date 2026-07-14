<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Support\AdListingPredicate;
use Illuminate\Support\Facades\DB;

it('defines active partial indexes that exclude soft-deleted ads', function (): void {
    $indexes = collect(DB::select(
        'SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?',
        ['ads'],
    ))->keyBy('indexname');

    foreach (AdListingPredicate::PARTIAL_INDEX_NAMES as $name) {
        expect($indexes->has($name))->toBeTrue("Missing index {$name}");

        $definition = (string) $indexes->get($name)->indexdef;

        expect($definition)
            ->toContain("'active'")
            ->toContain('deleted_at IS NULL');
    }
});

it('counts published ads without scanning the ads heap', function (): void {
    Ad::factory()->count(3)->create();

    $rows = Ad::query()
        ->published()
        ->toBase()
        ->selectRaw('count(*)')
        ->explain()
        ->all();

    $plan = strtolower(implode(' ', array_map(
        static fn (mixed $row): string => (string) ((array) $row)['QUERY PLAN'] ?? '',
        $rows,
    )));

    $allowedIndexes = array_merge(
        AdListingPredicate::PARTIAL_INDEX_NAMES,
        AdListingPredicate::PARTIAL_UNIQUE_INDEX_NAMES,
    );

    $pattern = '/index (only )?scan using ('.implode(
        '|',
        array_map(static fn (string $name): string => preg_quote($name, '/'), $allowedIndexes),
    ).')/';

    expect($plan)
        ->not->toContain('seq scan on ads')
        ->toMatch($pattern);
});
