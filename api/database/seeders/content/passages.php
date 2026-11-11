<?php

// Texts with questions about them (topic "tekstbegrip").
// Per text: title, body (plain text, a blank line starts a new paragraph) and its questions:
// type, level, difficulty, prompt, data (see App\Support\QuestionData), explanation.
return [
    [
        'title' => 'De fiets van Daan',
        'body' => <<<'TXT'
Daan fietste elke ochtend over de dijk naar school. Het was een oude fiets met een rammelend spatbord, maar hij hield ervan.

Op maandag was de fiets verdwenen. Daan zocht eerst bij de schuur en daarna achter het huis van de buren. Niets. Hij belde zijn vader, die op zijn werk zat. ‘Heb je hem wel op slot gezet?’ vroeg zijn vader. Daan werd rood. Dat was hij vergeten.

Na school liep Daan langs het politiebureau om aangifte te doen. Daar stond zijn fiets, tegen de muur. Een agent had hem in de gracht gevonden en uit het water gehaald. ‘Gelukkig staat je naam onder het zadel,’ zei de agent. Daan lachte opgelucht, maar hij zette zijn fiets die avond wel goed op slot.
TXT,
        'questions' => [
            [
                'type' => 'multiple_choice', 'level' => 'vmbo', 'difficulty' => 1,
                'prompt' => 'Waarom werd Daan rood?',
                'data' => [
                    'options' => [
                        'Hij had zijn fiets niet op slot gezet.',
                        'Hij was te laat op school gekomen.',
                        'Hij had zijn vader boos gemaakt.',
                        'Hij schaamde zich voor zijn oude fiets.',
                    ],
                    'correct' => [0],
                ],
                'explanation' => "Daans vader vraagt: 'Heb je hem wel op slot gezet?' Daan werd rood, want 'dat was hij vergeten'.\n\nJe leest dus eerst de vraag van zijn vader en dan hoe Daan reageert. Zo vind je de reden.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'vmbo', 'difficulty' => 2,
                'prompt' => 'Waar had de agent de fiets gevonden?',
                'data' => [
                    'options' => [
                        'Achter het huis van de buren.',
                        'In de schuur.',
                        'In de gracht.',
                        'Bij het politiebureau.',
                    ],
                    'correct' => [2],
                ],
                'explanation' => "De fiets stond bij het politiebureau, maar de vraag is waar de agent hem had gevonden. Dat staat in de laatste alinea: 'Een agent had hem in de gracht gevonden.'\n\nLet op: lees goed wat er precies gevraagd wordt. Waar de fiets nu staat is iets anders dan waar hij gevonden is.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'vmbo', 'difficulty' => 1,
                'prompt' => 'Hoe wist de agent dat de fiets van Daan was?',
                'data' => [
                    'options' => [
                        'Er stond de naam van Daan onder het zadel.',
                        'Daan had de fiets precies beschreven.',
                        'Daans vader had gebeld.',
                        'De fiets had een rammelend spatbord.',
                    ],
                    'correct' => [0],
                ],
                'explanation' => "De agent zegt: 'Gelukkig staat je naam onder het zadel.' Dat is het bewijs dat de fiets van Daan was.",
            ],
            [
                'type' => 'fill_blank', 'level' => 'vmbo', 'difficulty' => 1,
                'prompt' => 'Vul het ontbrekende woord uit de tekst in.',
                'data' => [
                    'text' => 'Daan fietste elke ochtend over de {{blank}} naar school.',
                    'blanks' => [['accepted' => ['dijk'], 'hint' => 'Een hoge rand langs het water']],
                    'case_sensitive' => false,
                    'ignore_extra_whitespace' => true,
                ],
                'explanation' => "In de allereerste zin van de tekst staat: 'Daan fietste elke ochtend over de dijk naar school.' Zoek bij zo'n vraag de zin op in de tekst.",
            ],
        ],
    ],
    [
        'title' => 'Een dag zonder telefoon',
        'body' => <<<'TXT'
Lotte legde haar telefoon in de la van de keukenkast en deed de deur dicht. Een hele zaterdag zonder scherm: dat had ze zichzelf beloofd, nadat haar klasgenoten in de pauze om haar hadden gelachen omdat ze elke minuut op haar scherm keek.

De eerste uren waren moeilijk. Steeds greep haar hand naar een zak waar niets in zat. Ze ruimde haar kamer op, las een paar bladzijden in een boek dat al maanden op haar nachtkastje lag en maakte een wandeling door het bos. Daar zag ze een ree, die haar even aankeek en toen wegsprong. Ze wilde het meteen aan iemand laten zien, maar er was niemand.

Aan het eind van de middag ging de vaste telefoon in de gang. Het was oma: of Lotte zin had om te komen eten. ‘Ik wist niet of je mijn bericht had gezien,’ zei oma. Lotte vertelde over de ree en over het boek, en voor het eerst in lange tijd keek er iemand haar aan terwijl ze praatte.

Toen ze thuiskwam, haalde ze haar telefoon uit de la. Er waren vijfentwintig berichten. Ze las ze niet allemaal; ze legde het toestel op de kast en ging slapen.
TXT,
        'questions' => [
            [
                'type' => 'multiple_choice', 'level' => 'havo', 'difficulty' => 2,
                'prompt' => 'Waarom besloot Lotte een dag zonder telefoon te doen?',
                'data' => [
                    'options' => [
                        'Haar telefoon was kapot.',
                        'Haar klasgenoten lachten erom dat ze steeds op haar scherm keek.',
                        'Haar oma had haar dat gevraagd.',
                        'Ze wilde een boek uitlezen.',
                    ],
                    'correct' => [1],
                ],
                'explanation' => "Dat staat in de eerste alinea: ze had het zichzelf beloofd 'nadat haar klasgenoten in de pauze om haar hadden gelachen omdat ze elke minuut op haar scherm keek'.\n\nHet boek komt pas later in de tekst voor, als iets wat ze op die dag deed. Het is dus niet de reden.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'havo', 'difficulty' => 2,
                'prompt' => 'Wat deed Lotte op haar dag zonder telefoon? Kies alle goede antwoorden.',
                'data' => [
                    'options' => [
                        'Ze ruimde haar kamer op.',
                        'Ze maakte een wandeling door het bos.',
                        'Ze ging bij haar klasgenoten op bezoek.',
                        'Ze las een paar bladzijden in een boek.',
                    ],
                    'correct' => [0, 1, 3],
                ],
                'explanation' => "In de tweede alinea staat wat ze deed: 'Ze ruimde haar kamer op, las een paar bladzijden in een boek (...) en maakte een wandeling door het bos.'\n\nVan een bezoek aan klasgenoten staat niets in de tekst.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'havo', 'difficulty' => 3,
                'prompt' => "Wat laat de zin 'Steeds greep haar hand naar een zak waar niets in zat' zien?",
                'data' => [
                    'options' => [
                        'Dat Lotte haar telefoon kwijt was.',
                        'Dat Lotte gewend was aan haar telefoon en hem miste.',
                        'Dat Lotte kou had aan haar handen.',
                        'Dat Lotte iets anders zocht.',
                    ],
                    'correct' => [1],
                ],
                'explanation' => "De telefoon zit niet in haar zak, want hij ligt in de la. Toch pakt haar hand er steeds naar. Dat doe je alleen uit gewoonte: ze is zo gewend aan haar telefoon dat ze hem mist.\n\nBij zo'n vraag staat het antwoord niet letterlijk in de tekst. Je moet zelf bedenken wat de zin betekent.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'havo', 'difficulty' => 3,
                'prompt' => "Wat zegt de laatste zin ('Ze las ze niet allemaal; ...') over Lotte?",
                'data' => [
                    'options' => [
                        'Dat Lotte boos was op haar vrienden.',
                        'Dat Lotte niet meer meteen op elk bericht wilde reageren.',
                        'Dat Lotte de berichten vergeten was.',
                        'Dat de batterij van haar telefoon leeg was.',
                    ],
                    'correct' => [1],
                ],
                'explanation' => "Lotte legt haar telefoon weg zonder alles te lezen en gaat slapen. Dat laat zien dat ze door haar dag offline anders met haar telefoon omgaat: ze voelt niet meer de drang om meteen alles te lezen.\n\nIn de tekst staat niets over boosheid of een lege batterij.",
            ],
        ],
    ],
    [
        'title' => 'De laatste uitleenbalie',
        'body' => <<<'TXT'
Op de laatste dag dat de wijkbibliotheek openging, stond er een rij tot op straat. Dat was opvallend, want in de maanden daarvoor was de balie vaak urenlang leeg geweest. De gemeente had besloten het pand te verkopen; boeken konden voortaan online worden besteld en aan huis worden bezorgd.

Mevrouw Aydin, die er eenendertig jaar had gewerkt, stempelde voor het laatst een datum in een boek. Ze deed het zonder commentaar, maar haar collega zag dat ze de stempel langer vasthield dan nodig was. ‘Het gaat niet om de boeken,’ zei een oudere man tegen de krant. ‘Het gaat erom dat je hier iemand kunt vragen wat je moet lezen, en dat die persoon het ook echt weet.’

Een wethouder verdedigde het besluit met cijfers: het aantal uitleningen was in tien jaar gehalveerd. Dat klopte, gaf een buurtbewoonster toe, maar zij vroeg zich af of je een bibliotheek moet afrekenen op wat ze uitleent, en niet op wat ze betekent. Daar had de wethouder geen kant-en-klaar antwoord op.

Aan het eind van de middag was de rij verdwenen. Mevrouw Aydin deed het licht uit, draaide de deur op slot en liet de sleutel, anders dan afgesproken, niet bij de gemeente achter, maar in haar zak glijden.
TXT,
        'questions' => [
            [
                'type' => 'multiple_choice', 'level' => 'vwo', 'difficulty' => 3,
                'prompt' => 'Waarom was het opvallend dat er een rij stond?',
                'data' => [
                    'options' => [
                        'Omdat het op de laatste dag gratis was.',
                        'Omdat de balie in de maanden daarvoor vaak leeg was geweest.',
                        'Omdat de bibliotheek eigenlijk al gesloten was.',
                        'Omdat de gemeente iedereen had uitgenodigd.',
                    ],
                    'correct' => [1],
                ],
                'explanation' => "In de eerste alinea staat: 'Dat was opvallend, want in de maanden daarvoor was de balie vaak urenlang leeg geweest.' Het woord 'want' wijst naar de reden.\n\nLet op signaalwoorden als 'want', 'omdat' en 'daarom': ze laten zien waar het antwoord staat.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'vwo', 'difficulty' => 3,
                'prompt' => 'Welk argument gebruikte de wethouder om het besluit te verdedigen?',
                'data' => [
                    'options' => [
                        'Het aantal uitleningen was in tien jaar gehalveerd.',
                        'De bibliotheek verloor veel boeken door diefstal.',
                        'Online bestellen is voor bezoekers goedkoper.',
                        'Het pand was te oud om te verbouwen.',
                    ],
                    'correct' => [0],
                ],
                'explanation' => "In de derde alinea verdedigt de wethouder het besluit 'met cijfers': het aantal uitleningen was in tien jaar gehalveerd.\n\nDat boeken voortaan online besteld kunnen worden staat in de eerste alinea, maar dat is het besluit zelf en geen argument van de wethouder. Over kosten, diefstal of het pand staat niets in de tekst.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'vwo', 'difficulty' => 4,
                'prompt' => 'Wat wil de buurtbewoonster zeggen met haar vraag in de derde alinea?',
                'data' => [
                    'options' => [
                        'Dat de cijfers van de wethouder niet kloppen.',
                        'Dat je de waarde van een bibliotheek niet alleen aan het aantal uitleningen kunt afmeten.',
                        'Dat de bibliotheek meer boeken moet aanschaffen.',
                        'Dat de wethouder zijn werk niet goed doet.',
                    ],
                    'correct' => [1],
                ],
                'explanation' => "De buurtbewoonster geeft toe dat de cijfers kloppen ('Dat klopte'). Haar punt is een ander: een bibliotheek moet je niet afrekenen op wat ze uitleent, maar op 'wat ze betekent'. Ze vindt dus dat uitleencijfers niet de hele waarde laten zien.\n\nHet eerste antwoord is fout, omdat zij de cijfers juist niet betwist.",
            ],
            [
                'type' => 'multiple_choice', 'level' => 'vwo', 'difficulty' => 4,
                'prompt' => 'Wat suggereert de laatste zin over mevrouw Aydin?',
                'data' => [
                    'options' => [
                        'Dat ze de sleutel per ongeluk meenam.',
                        'Dat het afscheid haar zwaar valt en dat ze iets van de bibliotheek wil bewaren.',
                        'Dat ze van plan is de bibliotheek weer te openen.',
                        'Dat de gemeente de sleutel niet meer nodig had.',
                    ],
                    'correct' => [1],
                ],
                'explanation' => "Mevrouw Aydin handelt 'anders dan afgesproken': ze laat de sleutel niet bij de gemeente achter. Dat doet ze niet per ongeluk, want ze 'liet hem in haar zak glijden'. Eerder in de tekst hield ze ook al de stempel langer vast dan nodig was.\n\nDe schrijver zegt het niet met zoveel woorden, maar laat het zien aan haar gedrag. Zo'n vraag vraagt dus om lezen 'tussen de regels'.",
            ],
        ],
    ],
];
