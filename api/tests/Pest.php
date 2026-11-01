<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| API test helpers
|--------------------------------------------------------------------------
*/

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;

/** Sign in as a (new) student with Sanctum and return the user. */
function student(?User $user = null): User
{
    $user ??= User::factory()->create();
    test()->actingAs($user, 'sanctum');

    return $user;
}

/**
 * An exam with $count multiple choice questions (correct option = index 1), worth $points each
 * (or the given list of points), in sort order.
 *
 * @param  int|list<int>  $points
 */
function examWithQuestions(int $count = 3, int|array $points = 1, array $attributes = []): Exam
{
    $exam = Exam::factory()->create($attributes);

    foreach (range(1, $count) as $position) {
        $question = Question::factory()->create(['level_id' => $exam->level_id]);
        $exam->questions()->attach($question->id, [
            'points' => is_array($points) ? $points[$position - 1] : $points,
            'sort_order' => $position,
        ]);
    }

    return $exam->load('questions');
}
