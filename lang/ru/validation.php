<?php

declare(strict_types=1);

return [
    'required' => 'Поле :attribute обязательно.',
    'email' => 'Поле :attribute должно быть действительным адресом электронной почты.',
    'min' => [
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
    ],
    'max' => [
        'string' => 'Поле :attribute не может быть длиннее :max символов.',
    ],
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'unique' => 'Такой :attribute уже занят.',
    'attributes' => [
        'email' => 'e-mail',
        'password' => 'пароль',
        'name' => 'имя',
        'title' => 'заголовок',
        'description' => 'описание',
        'category_id' => 'категория',
        'price' => 'цена',
        'location' => 'местоположение',
    ],
];
