<?php

declare(strict_types=1);

return [
    'required' => 'Pole :attribute jest wymagane.',
    'email' => 'Pole :attribute musi być poprawnym adresem e-mail.',
    'min' => [
        'string' => 'Pole :attribute musi mieć co najmniej :min znaków.',
    ],
    'max' => [
        'string' => 'Pole :attribute nie może być dłuższe niż :max znaków.',
    ],
    'confirmed' => 'Potwierdzenie pola :attribute nie zgadza się.',
    'unique' => 'Taki :attribute jest już zajęty.',
    'attributes' => [
        'email' => 'e-mail',
        'password' => 'hasło',
        'name' => 'imię i nazwisko',
        'title' => 'tytuł',
        'description' => 'opis',
        'category_id' => 'kategoria',
        'price' => 'cena',
        'location' => 'lokalizacja',
    ],
];
