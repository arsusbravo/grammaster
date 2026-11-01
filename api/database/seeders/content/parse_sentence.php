<?php

// Parse the sentence: data = tokens[]{text, role} + roles[] (options offered to the student).
return [
    [
        'topic' => 'zinsontleding', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'De kat', 'role' => 'onderwerp'],
                ['text' => 'eet', 'role' => 'persoonsvorm'],
                ['text' => 'de vis', 'role' => 'lijdend voorwerp'],
            ],
            'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'],
        ],
        'explanation' => "De persoonsvorm vind je door de zin in de verleden tijd te zetten: de kat at. Het woord dat verandert is 'eet'.\n\nHet onderwerp vind je met: wie of wat eet? De kat. Het lijdend voorwerp met: wat eet de kat? De vis.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'vmbo', 'difficulty' => 2,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'Mijn buurman', 'role' => 'onderwerp'],
                ['text' => 'fietst', 'role' => 'persoonsvorm'],
                ['text' => 'elke dag', 'role' => 'bijwoordelijke bepaling'],
                ['text' => 'naar zijn werk', 'role' => 'bijwoordelijke bepaling'],
            ],
            'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'bijwoordelijke bepaling'],
        ],
        'explanation' => "'Fietst' is de persoonsvorm en 'mijn buurman' het onderwerp (wie fietst?).\n\n'Elke dag' (wanneer?) en 'naar zijn werk' (waarheen?) vertellen meer over het fietsen. Zulke zinsdelen heten bijwoordelijke bepalingen. Een zin kan er meer dan één hebben.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'mavo', 'difficulty' => 2,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'Gisteren', 'role' => 'bijwoordelijke bepaling'],
                ['text' => 'gaf', 'role' => 'persoonsvorm'],
                ['text' => 'Tom', 'role' => 'onderwerp'],
                ['text' => 'zijn zus', 'role' => 'meewerkend voorwerp'],
                ['text' => 'een cadeau', 'role' => 'lijdend voorwerp'],
            ],
            'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'meewerkend voorwerp', 'bijwoordelijke bepaling'],
        ],
        'explanation' => "Persoonsvorm: gaf. Onderwerp: wie gaf? Tom. Lijdend voorwerp: wat gaf Tom? Een cadeau.\n\nHet meewerkend voorwerp is degene die iets krijgt. Je kunt er 'aan' of 'voor' voor zetten: aan zijn zus. 'Gisteren' is een bijwoordelijke bepaling van tijd.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'havo', 'difficulty' => 3,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'De leraar', 'role' => 'onderwerp'],
                ['text' => 'is', 'role' => 'persoonsvorm'],
                ['text' => 'erg streng', 'role' => 'naamwoordelijk deel van het gezegde'],
            ],
            'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'naamwoordelijk deel van het gezegde'],
        ],
        'explanation' => "Het werkwoord 'zijn' is hier een koppelwerkwoord. Het koppelt het onderwerp aan een eigenschap. Samen met 'erg streng' vormt 'is' een naamwoordelijk gezegde.\n\n'Erg streng' zegt iets over de leraar. Het is dus geen lijdend voorwerp: je kunt niet vragen 'wat is de leraar?' met een voorwerp als antwoord.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'vwo', 'difficulty' => 4,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'Volgens de krant', 'role' => 'bijwoordelijke bepaling'],
                ['text' => 'hebben', 'role' => 'persoonsvorm'],
                ['text' => 'de onderzoekers', 'role' => 'onderwerp'],
                ['text' => 'een nieuwe methode', 'role' => 'lijdend voorwerp'],
                ['text' => 'ontwikkeld', 'role' => 'werkwoordelijk deel van het gezegde'],
            ],
            'roles' => [
                'onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'meewerkend voorwerp',
                'bijwoordelijke bepaling', 'werkwoordelijk deel van het gezegde',
            ],
        ],
        'explanation' => "Het gezegde bestaat uit 'hebben' (persoonsvorm) en 'ontwikkeld' (werkwoordelijk deel van het gezegde). Het onderwerp is 'de onderzoekers' (wie hebben ontwikkeld?) en het lijdend voorwerp 'een nieuwe methode' (wat hebben ze ontwikkeld?).\n\n'Volgens de krant' geeft aan waar de informatie vandaan komt en is een bijwoordelijke bepaling. Let op: het onderwerp staat hier ná de persoonsvorm omdat de zin met een bepaling begint.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'vwo', 'difficulty' => 3,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'Mijn oma', 'role' => 'onderwerp'],
                ['text' => 'vertelde', 'role' => 'persoonsvorm'],
                ['text' => 'ons', 'role' => 'meewerkend voorwerp'],
                ['text' => 'gisteren', 'role' => 'bijwoordelijke bepaling'],
                ['text' => 'een verhaal', 'role' => 'lijdend voorwerp'],
            ],
            'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'meewerkend voorwerp', 'bijwoordelijke bepaling'],
        ],
        'explanation' => "Persoonsvorm: vertelde (zet de zin in de tegenwoordige tijd: vertelt). Onderwerp: wie vertelde? Mijn oma. Lijdend voorwerp: wat vertelde ze? Een verhaal.\n\n'Ons' kun je vervangen door 'aan ons': het is het meewerkend voorwerp. 'Gisteren' beantwoordt de vraag 'wanneer?' en is een bijwoordelijke bepaling.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'university', 'difficulty' => 4,
        'prompt' => 'Wat is de functie van elk zinsdeel in deze zin?',
        'data' => [
            'tokens' => [
                ['text' => 'Volgens de woordvoerder', 'role' => 'bijwoordelijke bepaling'],
                ['text' => 'heeft', 'role' => 'persoonsvorm'],
                ['text' => 'de minister', 'role' => 'onderwerp'],
                ['text' => 'de Kamer', 'role' => 'meewerkend voorwerp'],
                ['text' => 'gisteren', 'role' => 'bijwoordelijke bepaling'],
                ['text' => 'een brief', 'role' => 'lijdend voorwerp'],
                ['text' => 'gestuurd', 'role' => 'werkwoordelijk deel van het gezegde'],
            ],
            'roles' => [
                'onderwerp', 'persoonsvorm', 'lijdend voorwerp', 'meewerkend voorwerp',
                'bijwoordelijke bepaling', 'werkwoordelijk deel van het gezegde',
            ],
        ],
        'explanation' => "Het gezegde is 'heeft ... gestuurd': 'heeft' is de persoonsvorm en 'gestuurd' het werkwoordelijk deel van het gezegde. Onderwerp: wie heeft gestuurd? De minister. Lijdend voorwerp: wat heeft de minister gestuurd? Een brief. Meewerkend voorwerp: aan wie? Aan de Kamer.\n\nEr zijn twee bijwoordelijke bepalingen: 'volgens de woordvoerder' (bron) en 'gisteren' (tijd). De zin begint met een bepaling, daarom staat het onderwerp pas na de persoonsvorm.",
    ],
];
