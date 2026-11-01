<?php

// Multiple choice: data = options[] + correct[] (indexes into options).
return [
    [
        'topic' => 'werkwoordspelling', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Welke zin is goed gespeld?',
        'data' => [
            'options' => ['Hij word morgen veertien.', 'Hij wordt morgen veertien.', 'Hij wort morgen veertien.'],
            'correct' => [1],
        ],
        'explanation' => "Bij 'hij' krijgt het werkwoord in de tegenwoordige tijd een -t achter de stam. De stam van 'worden' is 'word', want je hoort een d in 'worden'. Dus: word + t = hij wordt.\n\nTip: zeg het hele werkwoord (worden) hardop. Dan hoor je welke letter er aan het eind van de stam staat.",
    ],
    [
        'topic' => 'lidwoorden', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Welk lidwoord hoort bij het woord "tafel"?',
        'data' => ['options' => ['de', 'het'], 'correct' => [0]],
        'explanation' => "Je zegt 'de tafel'. Er is geen regel voor alle woorden, dus je moet het vaak onthouden of opzoeken in een woordenboek.\n\nWel handig om te weten: in het meervoud gebruik je altijd 'de' (de tafels), en een verkleinwoord is altijd een het-woord (het tafeltje).",
    ],
    [
        'topic' => 'voornaamwoorden', 'level' => 'mavo', 'difficulty' => 2,
        'prompt' => 'Welke zin is correct?',
        'data' => [
            'options' => ['Ik geef hen het boek.', 'Ik geef hun het boek.', 'Ik geef het boek aan hun.', 'Ik zie hun lopen.'],
            'correct' => [1],
        ],
        'explanation' => "'Hun' gebruik je als meewerkend voorwerp, zonder voorzetsel: 'Ik geef hun het boek' betekent 'Ik geef het boek aan hen'.\n\n'Hen' gebruik je als lijdend voorwerp (Ik zie hen lopen) of na een voorzetsel (aan hen, met hen).\n\nTip: als je het niet zeker weet, gebruik dan 'ze' of 'aan hen'. Dat is altijd goed.",
    ],
    [
        'topic' => 'zinsontleding', 'level' => 'havo', 'difficulty' => 2,
        'prompt' => 'Wat is het onderwerp in deze zin? "Gisteren heeft de buurman de heg gesnoeid."',
        'data' => ['options' => ['Gisteren', 'de buurman', 'de heg', 'heeft'], 'correct' => [1]],
        'explanation' => "Het onderwerp vind je met de vraag: wie of wat + de persoonsvorm? Wie heeft gesnoeid? De buurman.\n\n'De heg' is het lijdend voorwerp (wat heeft de buurman gesnoeid?), 'gisteren' is een bijwoordelijke bepaling van tijd en 'heeft' is de persoonsvorm.",
    ],
    [
        'topic' => 'samengestelde-woorden', 'level' => 'vwo', 'difficulty' => 3,
        'prompt' => 'Welk woord is goed geschreven?',
        'data' => ['options' => ['scheidrechter', 'scheidsrechter', 'scheidtsrechter'], 'correct' => [1]],
        'explanation' => "Het woord is 'scheidsrechter'. In veel samenstellingen komt tussen de twee delen een tussenklank: een -s-, -en- of -e-. Voor het gebruik van die tussenklank is geen vaste regel, dus je moet het woord onthouden of opzoeken in de Woordenlijst.\n\nDe samenstelling schrijf je wel altijd aan elkaar.",
    ],
    [
        'topic' => 'werkwoordspelling', 'level' => 'vwo', 'difficulty' => 3,
        'prompt' => 'Welk voltooid deelwoord is goed geschreven? "Het huis is vorig jaar helemaal ..."',
        'data' => ['options' => ['verbouwd', 'verbouwt', 'verbouwdt'], 'correct' => [0]],
        'explanation' => "Gebruik het ezelsbruggetje 't kofschip. Haal de stam van het hele werkwoord (verbouwen → verbouw). De stam eindigt op een w, en die staat niet in 't kofschip (t, k, f, s, ch, p). Daarom krijgt het voltooid deelwoord een -d: verbouwd.\n\nEen -t aan het eind kun je alleen schrijven als de stam eindigt op een van die letters, bijvoorbeeld 'gewerkt' (stam werk).",
    ],
    [
        'topic' => 'leestekens', 'level' => 'university', 'difficulty' => 4,
        'prompt' => 'Welke zinnen zijn correct geïnterpuncteerd? (Meer antwoorden mogelijk)',
        'data' => [
            'options' => [
                'De docent, die altijd te laat komt, gaf het tentamen.',
                'Hij vroeg of ik mee wilde, maar ik had geen tijd.',
                'Hij zei; dat hij kwam.',
                'Ik denk, dat het morgen regent.',
            ],
            'correct' => [0, 1],
        ],
        'explanation' => "Zin 1 is goed: een bijzin die alleen extra informatie geeft (niet nodig om te weten welke docent) zet je tussen komma's.\n\nZin 2 is goed: tussen twee hoofdzinnen die door 'maar' worden verbonden, schrijf je een komma.\n\nZin 3 is fout: een puntkomma scheidt twee zinnen die op zichzelf kunnen staan en staat niet tussen 'zei' en een 'dat'-bijzin. Zin 4 is fout: voor een 'dat'-bijzin die het object van het werkwoord is, schrijf je geen komma.",
    ],
];
