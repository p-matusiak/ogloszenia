<?php

declare(strict_types=1);

use App\Support\PhoneMasker;

it('keeps the first five digits and hides the rest', function (): void {
    expect((new PhoneMasker)->mask('+48 600 123 456'))->toBe('+48 600 ••• •••');
});

it('preserves the shape of an unformatted number', function (): void {
    expect((new PhoneMasker)->mask('600123456'))->toBe('60012••••');
});

it('leaves separators alone so the mask does not reveal the digit count', function (): void {
    expect((new PhoneMasker)->mask('600-123-456'))->toBe('600-12•-•••');
});

it('returns nothing for a missing number', function (): void {
    expect((new PhoneMasker)->mask(null))->toBeNull()
        ->and((new PhoneMasker)->mask(''))->toBeNull();
});

it('does not mask a number shorter than the visible window', function (): void {
    expect((new PhoneMasker)->mask('112'))->toBe('112');
});
