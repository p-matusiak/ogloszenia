<?php

declare(strict_types=1);

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',
    'unique' => 'The :attribute has already been taken.',
    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'name' => 'name',
        'title' => 'title',
        'description' => 'description',
        'category_id' => 'category',
        'price' => 'price',
        'location' => 'location',
    ],
];
