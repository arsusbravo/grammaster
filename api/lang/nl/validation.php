<?php

// Dutch validation messages for the rules this API uses. Missing keys fall back to English.
return [
    'array' => ':Attribute moet een lijst zijn.',
    'current_password' => 'Het huidige wachtwoord is onjuist.',
    'different' => ':Attribute moet anders zijn dan :other.',
    // Laravel's Password rule
    'password' => [
        'letters' => ':Attribute moet minimaal één letter bevatten.',
        'mixed' => ':Attribute moet minimaal één hoofdletter en één kleine letter bevatten.',
        'numbers' => ':Attribute moet minimaal één cijfer bevatten.',
        'symbols' => ':Attribute moet minimaal één speciaal teken bevatten.',
        'uncompromised' => 'Dit :attribute komt voor in een datalek. Kies een ander :attribute.',
        'min' => ':Attribute moet minimaal :min tekens bevatten.',
    ],
    'between' => [
        'array' => ':Attribute moet tussen :min en :max items bevatten.',
        'numeric' => ':Attribute moet tussen :min en :max liggen.',
        'string' => ':Attribute moet tussen :min en :max tekens bevatten.',
    ],
    'boolean' => ':Attribute moet ja of nee zijn.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'distinct' => ':Attribute bevat een dubbele waarde.',
    'email' => ':Attribute moet een geldig e-mailadres zijn.',
    'exists' => 'De gekozen :attribute is ongeldig.',
    'in' => 'De gekozen :attribute is ongeldig.',
    'integer' => ':Attribute moet een geheel getal zijn.',
    'max' => [
        'array' => ':Attribute mag niet meer dan :max items bevatten.',
        'numeric' => ':Attribute mag niet groter zijn dan :max.',
        'string' => ':Attribute mag niet uit meer dan :max tekens bestaan.',
    ],
    'min' => [
        'array' => ':Attribute moet minimaal :min items bevatten.',
        'numeric' => ':Attribute moet minimaal :min zijn.',
        'string' => ':Attribute moet minimaal :min tekens bevatten.',
    ],
    'required' => ':Attribute is verplicht.',
    'size' => [
        'array' => ':Attribute moet :size items bevatten.',
        'numeric' => ':Attribute moet :size zijn.',
        'string' => ':Attribute moet :size tekens bevatten.',
    ],
    'string' => ':Attribute moet tekst zijn.',
    'unique' => ':Attribute is al in gebruik.',

    'attributes' => [
        'name' => 'naam',
        'email' => 'e-mailadres',
        'password' => 'wachtwoord',
        'current_password' => 'huidige wachtwoord',
        'level_id' => 'niveau',
        'level' => 'niveau',
        'topic' => 'onderwerp',
        'difficulty' => 'moeilijkheid',
        'answer' => 'antwoord',
        'limit' => 'aantal',
    ],
];
