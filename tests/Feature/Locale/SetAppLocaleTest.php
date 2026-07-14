<?php

declare(strict_types=1);

it('uses polish validation messages when Accept-Language is pl', function (): void {
    $this->postJson('/api/v1/auth/register', [], [
        'Accept-Language' => 'pl-PL',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'name']);
});

it('uses english validation messages when Accept-Language is en', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [], [
        'Accept-Language' => 'en-US',
    ])->assertUnprocessable();

    expect($response->json('errors.email.0'))->toContain('email field');
});

it('uses russian validation messages when Accept-Language is ru', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [], [
        'Accept-Language' => 'ru-RU',
    ])->assertUnprocessable();

    expect($response->json('errors.email.0'))->toContain('Поле');
});
