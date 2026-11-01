<?php

// Fill in the blank: data = text with {{blank}} markers + blanks[]{accepted[], hint?} + case rules.
return [
    [
        'topic' => 'werkwoordspelling', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Vul de juiste vorm van het werkwoord in.',
        'data' => [
            'text' => 'Mijn broer {{blank}} in een ziekenhuis.',
            'blanks' => [['accepted' => ['werkt'], 'hint' => 'werken']],
            'case_sensitive' => false,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => "'Mijn broer' kun je vervangen door 'hij'. Bij 'hij' krijgt het werkwoord een -t achter de stam: werk + t = werkt.\n\nJe schrijft maar één t, want de stam 'werk' eindigt niet op een t.",
    ],
    [
        'topic' => 'hoofdletters', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Schrijf de plaatsnaam op de juiste manier.',
        'data' => [
            'text' => 'Ik woon in {{blank}}.',
            'blanks' => [['accepted' => ['Amsterdam'], 'hint' => 'amsterdam']],
            'case_sensitive' => true,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => 'Namen van plaatsen, landen en mensen schrijf je met een hoofdletter: Amsterdam, Nederland, Fatima. Hier telt de hoofdletter dus mee in je antwoord.',
    ],
    [
        'topic' => 'lidwoorden', 'level' => 'mavo', 'difficulty' => 2,
        'prompt' => 'Vul het juiste lidwoord in (de of het).',
        'data' => [
            'text' => 'Ik zie {{blank}} meisje dat daar loopt.',
            'blanks' => [['accepted' => ['het'], 'hint' => 'de of het']],
            'case_sensitive' => false,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => "Het is 'het meisje'. Meisje is een verkleinwoord (het eindigt op -je) en verkleinwoorden zijn altijd het-woorden, ook al gaat het over een persoon die vrouwelijk is. Ook: het kindje, het huisje, het mannetje.",
    ],
    [
        'topic' => 'voornaamwoorden', 'level' => 'havo', 'difficulty' => 3,
        'prompt' => 'Vul hen of hun in.',
        'data' => [
            'text' => 'Sam en Noor hebben een vraag. Ik wil {{blank}} iets vertellen.',
            'blanks' => [['accepted' => ['hun'], 'hint' => 'hen of hun']],
            'case_sensitive' => false,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => "Ik vertel iets aan Sam en Noor: 'iets' is het lijdend voorwerp en 'Sam en Noor' het meewerkend voorwerp. Een meewerkend voorwerp zonder voorzetsel krijgt 'hun'.\n\nTest: kun je 'aan hen' invullen zonder dat de zin raar wordt (Ik wil iets aan hen vertellen)? Dan schrijf je zonder voorzetsel 'hun'.",
    ],
    [
        'topic' => 'werkwoordspelling', 'level' => 'vwo', 'difficulty' => 4,
        'prompt' => 'Vul de juiste vorm van het werkwoord schrijven in.',
        'data' => [
            'text' => '{{blank}} jij de brief? Nee, ik denk dat zij hem {{blank}}.',
            'blanks' => [
                ['accepted' => ['schrijf'], 'hint' => 'schrijven'],
                ['accepted' => ['schrijft'], 'hint' => 'schrijven'],
            ],
            'case_sensitive' => false,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => "Bij inversie (het werkwoord staat vóór 'jij') verdwijnt de -t: 'Schrijf jij?'. Staat 'jij' ná het werkwoord, dan schrijf je alleen de stam.\n\nIn 'dat zij hem schrijft' staat 'zij' vóór het werkwoord. Bij hij, zij en het hoort in de tegenwoordige tijd de stam + t: schrijft.",
    ],
    [
        'topic' => 'samengestelde-woorden', 'level' => 'vwo', 'difficulty' => 3,
        'prompt' => 'Maak één woord van "kind" en "opvang".',
        'data' => [
            'text' => 'Een plek waar kinderen overdag worden opgevangen, heet {{blank}}.',
            'blanks' => [['accepted' => ['kinderopvang'], 'hint' => 'kind + opvang']],
            'case_sensitive' => false,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => "Samenstellingen schrijf je aan elkaar: kinderopvang. Let op de tussenklank: het eerste deel is 'kinder-' en niet 'kind-' of 'kinderen-'.\n\nVoor de tussenklank bestaat geen vaste regel. Twijfel je? Zoek het woord op in de Woordenlijst Nederlandse Taal.",
    ],
    [
        'topic' => 'leestekens', 'level' => 'university', 'difficulty' => 4,
        'prompt' => 'Welk leesteken ontbreekt op de open plek?',
        'data' => [
            'text' => 'Hij beloofde twee dingen{{blank}} een bonus en extra vakantie.',
            'blanks' => [['accepted' => [':'], 'hint' => 'leesteken']],
            'case_sensitive' => false,
            'ignore_extra_whitespace' => true,
        ],
        'explanation' => "Een dubbele punt (:) kondigt een opsomming, een toelichting of een aanhaling aan. Hier legt wat na de dubbele punt uit wat de 'twee dingen' zijn.\n\nEen komma zou de opsomming verkeerd laten aansluiten en een puntkomma scheidt twee op zichzelf staande delen.",
    ],
];
