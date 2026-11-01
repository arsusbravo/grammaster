<?php

// Order words: data = words[] in the correct order (the API shuffles), optional alternative_orders[][].
return [
    [
        'topic' => 'zinsontleding', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Zet de woorden in de goede volgorde.',
        'data' => ['words' => ['De', 'kat', 'slaapt', 'op', 'de', 'bank'], 'case_sensitive' => false],
        'explanation' => 'In een gewone zin staat eerst het onderwerp (De kat), daarna de persoonsvorm (slaapt) en dan de rest (op de bank). Een zin begint met een hoofdletter.',
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'vmbo', 'difficulty' => 2,
        'prompt' => 'Zet de woorden in de goede volgorde. Er is meer dan één goede volgorde.',
        'data' => [
            'words' => ['Gisteren', 'ging', 'ik', 'zwemmen'],
            'alternative_orders' => [['Ik', 'ging', 'gisteren', 'zwemmen']],
            'case_sensitive' => false,
        ],
        'explanation' => "De persoonsvorm (ging) staat in een zin altijd op de tweede plaats. Op de eerste plaats mag het onderwerp staan (Ik ging gisteren zwemmen) of een ander zinsdeel zoals 'gisteren'. Dan komt het onderwerp na de persoonsvorm (Gisteren ging ik zwemmen).",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'mavo', 'difficulty' => 2,
        'prompt' => 'Zet de woorden in de goede volgorde.',
        'data' => ['words' => ['Ik', 'weet', 'dat', 'hij', 'morgen', 'komt'], 'case_sensitive' => false],
        'explanation' => "Na 'dat' begint een bijzin. In een bijzin staat de persoonsvorm (komt) aan het eind van de zin, na het onderwerp (hij) en de bepaling (morgen). In de hoofdzin 'Ik weet' staat de persoonsvorm op de tweede plaats.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'havo', 'difficulty' => 3,
        'prompt' => 'Zet de woorden in de goede volgorde. Er is meer dan één goede volgorde.',
        'data' => [
            'words' => ['Omdat', 'het', 'regende', 'bleven', 'wij', 'binnen'],
            'alternative_orders' => [['Wij', 'bleven', 'binnen', 'omdat', 'het', 'regende']],
            'case_sensitive' => false,
        ],
        'explanation' => "Een bijzin met 'omdat' kan voor of achter de hoofdzin staan. Staat de bijzin voorop, dan telt de hele bijzin als eerste zinsdeel. De persoonsvorm van de hoofdzin (bleven) staat dan direct daarna, vóór het onderwerp: 'Omdat het regende, bleven wij binnen'.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'vwo', 'difficulty' => 4,
        'prompt' => 'Zet de woorden in de goede volgorde. Er is meer dan één goede volgorde.',
        'data' => [
            'words' => ['Hoewel', 'de', 'toets', 'moeilijk', 'was', 'slaagde', 'iedereen'],
            'alternative_orders' => [['Iedereen', 'slaagde', 'hoewel', 'de', 'toets', 'moeilijk', 'was']],
            'case_sensitive' => false,
        ],
        'explanation' => "De bijzin 'hoewel de toets moeilijk was' heeft de persoonsvorm (was) aan het eind. De hoofdzin heeft de persoonsvorm (slaagde) op de tweede plaats. Staat de bijzin eerst, dan is die samen het eerste zinsdeel en volgt 'slaagde' direct daarna, vóór het onderwerp 'iedereen'.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'vwo', 'difficulty' => 3,
        'prompt' => 'Zet de woorden in de goede volgorde. Er is meer dan één goede volgorde.',
        'data' => [
            'words' => ['Hij', 'heeft', 'gisteren', 'het', 'boek', 'gelezen'],
            'alternative_orders' => [
                ['Hij', 'heeft', 'het', 'boek', 'gisteren', 'gelezen'],
                ['Gisteren', 'heeft', 'hij', 'het', 'boek', 'gelezen'],
                ['Het', 'boek', 'heeft', 'hij', 'gisteren', 'gelezen'],
            ],
            'case_sensitive' => false,
        ],
        'explanation' => "De persoonsvorm (heeft) staat op de tweede plaats. Het voltooid deelwoord (gelezen) staat in een hoofdzin aan het eind, samen met de persoonsvorm vormt dat het gezegde.\n\nHet eerste zinsdeel mag het onderwerp zijn, maar ook een bepaling (gisteren) of het lijdend voorwerp (het boek). De rest schuift dan mee.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'university', 'difficulty' => 5,
        'prompt' => 'Zet de woorden in de goede volgorde. Er is meer dan één goede volgorde.',
        'data' => [
            'words' => ['Naarmate', 'het', 'onderzoek', 'vorderde', 'werden', 'de', 'resultaten', 'duidelijker'],
            'alternative_orders' => [['De', 'resultaten', 'werden', 'duidelijker', 'naarmate', 'het', 'onderzoek', 'vorderde']],
            'case_sensitive' => false,
        ],
        'explanation' => "Een bijzin die vooropstaat, is samen het eerste zinsdeel van de hoofdzin. Daarom volgt de persoonsvorm van de hoofdzin (werden) direct op de bijzin en komt het onderwerp (de resultaten) daarna: inversie.\n\nStaat de bijzin achteraan, dan begint de zin met het onderwerp en staat de persoonsvorm op de tweede plaats.",
    ],
];
