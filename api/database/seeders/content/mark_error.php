<?php

// Mark the error: data = tokens[] + error_index + correction.
return [
    [
        'topic' => 'werkwoordspelling', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['Wij', 'fietst', 'naar', 'school'], 'error_index' => 1, 'correction' => 'fietsen'],
        'explanation' => "Bij 'wij' is het werkwoord in de tegenwoordige tijd hetzelfde als het hele werkwoord: wij fietsen. De -t hoort bij 'hij', 'zij' en 'het' (en bij 'jij' als het werkwoord vóór 'jij' staat).",
    ],
    [
        'topic' => 'hoofdletters', 'level' => 'vmbo', 'difficulty' => 1,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['Gisteren', 'ben', 'ik', 'naar', 'parijs', 'gegaan'], 'error_index' => 4, 'correction' => 'Parijs'],
        'explanation' => "Namen van steden schrijf je met een hoofdletter: Parijs. Voor gewone woorden zoals 'naar' of 'gegaan' gebruik je een kleine letter.",
    ],
    [
        'topic' => 'lidwoorden', 'level' => 'mavo', 'difficulty' => 2,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['Ik', 'heb', 'de', 'boek', 'gelezen'], 'error_index' => 2, 'correction' => 'het'],
        'explanation' => "'Boek' is een het-woord: het boek. Zeg je 'de boek', dan klopt het lidwoord niet bij het zelfstandig naamwoord. In het meervoud zeg je wel 'de boeken'.",
    ],
    [
        'topic' => 'werkwoordspelling', 'level' => 'havo', 'difficulty' => 3,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['De', 'toets', 'is', 'goed', 'gemaakd', 'door', 'Sam'], 'error_index' => 4, 'correction' => 'gemaakt'],
        'explanation' => "Gebruik 't kofschip. De stam van 'maken' is 'maak' en eindigt op een k. De letter k staat in 't kofschip (t, k, f, s, ch, p), dus het voltooid deelwoord krijgt een -t: gemaakt.",
    ],
    [
        'topic' => 'voornaamwoorden', 'level' => 'vwo', 'difficulty' => 4,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['Ik', 'zie', 'hun', 'staan', 'bij', 'de', 'bushalte'], 'error_index' => 2, 'correction' => 'hen'],
        'explanation' => "'Hun' kan alleen een meewerkend voorwerp zijn (Ik geef hun een boek). Hier is het voornaamwoord het lijdend voorwerp: wie zie ik? Hen. Daarom schrijf je 'Ik zie hen staan'.\n\nTip: vervang het door 'ze'. Past 'ze', dan is het meestal 'hen' (lijdend voorwerp) of 'hun' (meewerkend voorwerp).",
    ],
    [
        'topic' => 'hoofdletters', 'level' => 'vwo', 'difficulty' => 4,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['Hij', 'komt', 'uit', 'het', 'Noorden', 'van', 'het', 'land'], 'error_index' => 4, 'correction' => 'noorden'],
        'explanation' => 'Windrichtingen schrijf je met een kleine letter, ook als je er een gebied mee bedoelt: het noorden van het land. Een hoofdletter gebruik je alleen in eigennamen, zoals Noord-Holland of de Noordpool.',
    ],
    [
        'topic' => 'werkwoordspelling', 'level' => 'university', 'difficulty' => 4,
        'prompt' => 'Tik op het woord dat fout is.',
        'data' => ['tokens' => ['Burgers', 'worden', 'uitgenodigd', 'en', 'geraadpleegt'], 'error_index' => 4, 'correction' => 'geraadpleegd'],
        'explanation' => "De stam van 'raadplegen' is 'raadpleeg' en eindigt op een g. De g staat niet in 't kofschip (t, k, f, s, ch, p). Daarom krijgt het voltooid deelwoord een -d: geraadpleegd.\n\nAan het eind hoor je wel een t, maar voor de spelling kijk je naar de stam en niet naar wat je hoort.",
    ],
];
