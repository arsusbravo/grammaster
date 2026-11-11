import type {
  Passage,
  FillBlankQuestion,
  MarkErrorQuestion,
  MultipleChoiceQuestion,
  OrderWordsQuestion,
  ParseSentenceQuestion,
  AnswerResult,
} from '@/types/api'

// Sample questions in the shape the API sends (no solutions), plus API-style results.
// Used by the /design page and by the tests.

export const multipleChoice: MultipleChoiceQuestion = {
  id: 1,
  type: 'multiple_choice',
  prompt: 'Welke zin is goed gespeld?',
  difficulty: 1,
  data: {
    options: [
      'Hij word morgen veertien.',
      'Hij wordt morgen veertien.',
      'Hij wort morgen veertien.',
    ],
    multiple: false,
  },
}

export const passage: Passage = {
  id: 1,
  title: 'De fiets van Daan',
  body: 'Daan fietste elke ochtend over de dijk naar school. Het was een oude fiets met een rammelend spatbord.\n\nOp maandag was de fiets verdwenen. Daan zocht eerst bij de schuur en daarna achter het huis van de buren.\n\nNa school liep Daan langs het politiebureau. Daar stond zijn fiets, tegen de muur.',
}

/** A normal question that is about a text. */
export const passageQuestion: MultipleChoiceQuestion = {
  id: 7,
  type: 'multiple_choice',
  prompt: 'Waar stond de fiets van Daan aan het eind?',
  difficulty: 1,
  passage,
  data: {
    options: ['In de schuur.', 'Bij de buren.', 'Bij het politiebureau.'],
    multiple: false,
  },
}

export const multipleChoiceMulti: MultipleChoiceQuestion = {
  id: 2,
  type: 'multiple_choice',
  prompt: 'Welke zinnen zijn correct geïnterpuncteerd?',
  difficulty: 4,
  data: {
    options: [
      'De docent, die altijd te laat komt, gaf het tentamen.',
      'Hij vroeg of ik mee wilde, maar ik had geen tijd.',
      'Hij zei; dat hij kwam.',
    ],
    multiple: true,
  },
}

export const fillBlank: FillBlankQuestion = {
  id: 3,
  type: 'fill_blank',
  prompt: 'Vul de juiste vorm van het werkwoord in.',
  difficulty: 4,
  data: {
    text: '{{blank}} jij de brief? Nee, ik denk dat zij hem {{blank}}.',
    blanks: [{ hint: 'schrijven' }, { hint: 'schrijven' }],
  },
}

export const orderWords: OrderWordsQuestion = {
  id: 4,
  type: 'order_words',
  prompt: 'Zet de woorden in de goede volgorde.',
  difficulty: 2,
  data: { words: ['zwemmen', 'ik', 'Gisteren', 'ging'] },
}

export const markError: MarkErrorQuestion = {
  id: 5,
  type: 'mark_error',
  prompt: 'Tik op het woord dat fout is.',
  difficulty: 1,
  data: { tokens: ['Wij', 'fietst', 'naar', 'school'] },
}

export const parseSentence: ParseSentenceQuestion = {
  id: 6,
  type: 'parse_sentence',
  prompt: 'Wat is de functie van elk zinsdeel in deze zin?',
  difficulty: 1,
  data: {
    tokens: ['De kat', 'eet', 'de vis'],
    roles: ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'],
  },
}

export const questions = [multipleChoice, fillBlank, orderWords, markError, parseSentence]

export function result(
  questionId: number,
  isCorrect: boolean,
  correctAnswer: AnswerResult['correct_answer'],
  items: boolean[] | null = null,
): AnswerResult {
  return {
    question_id: questionId,
    attempt_id: 10,
    is_correct: isCorrect,
    items,
    correct_answer: correctAnswer,
    explanation: 'Dit is de uitleg.\n\nEn dit is een tweede alinea.',
  }
}
