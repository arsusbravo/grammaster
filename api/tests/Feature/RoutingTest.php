<?php

declare(strict_types=1);
use App\Models\Level;
use App\Models\Question;

it('shows the landing page on the home address with links to the student app', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Nederlandse grammatica oefenen', false)
        ->assertSee('href="/student/"', false)
        ->assertSee('href="/student/login"', false)
        ->assertSee('href="/student/registreren"', false);
});

it('offers sample questions on the landing page without giving away the answers', function (): void {
    $level = Level::factory()->create(['slug' => 'havo', 'name' => 'HAVO']);
    Question::factory()->create(['level_id' => $level->id, 'prompt' => 'Welke zin is goed gespeld?']);
    Question::factory()->fillBlank()->create(['level_id' => $level->id]);
    Question::factory()->markError()->create(['level_id' => $level->id]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Probeer het zelf')
        ->assertSee('HAVO')
        ->assertSee('Welke zin is goed gespeld?')
        ->assertSee('/api/v1/samples', false)
        ->assertSee('href="/student/registreren"', false)
        ->assertDontSee('error_index')
        ->assertDontSee('accepted')
        ->assertDontSee('data-correct');
});

it('leaves the sample section out when there are no questions yet', function (): void {
    $this->get('/')->assertOk()->assertDontSee('Probeer het zelf');
});

it('keeps the admin and the API on their own paths', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->getJson('/api/v1/ping')->assertOk()->assertExactJson(['status' => 'ok']);
});

it('serves the built student app for its page and for deep links', function (): void {
    $public = sys_get_temp_dir().'/gm-public-'.uniqid();
    mkdir($public.'/student', 0777, true);
    file_put_contents($public.'/student/index.html', '<!doctype html><title>app</title>');
    $this->app->usePublicPath($public);

    foreach (['/student/', '/student/examens/poging/7'] as $url) {
        $response = $this->get($url)->assertOk();

        expect($response->baseResponse->getFile()->getPathname())->toBe($public.'/student/index.html')
            ->and($response->headers->get('Cache-Control'))->toContain('no-cache');
    }

    $this->get('/student')->assertOk();
});

it('answers a missing student file with a 404 instead of the page', function (): void {
    $public = sys_get_temp_dir().'/gm-public-'.uniqid();
    mkdir($public.'/student', 0777, true);
    file_put_contents($public.'/student/index.html', '<!doctype html>');
    $this->app->usePublicPath($public);

    $this->get('/student/assets/old-123.js')->assertNotFound();
});

it('explains that the student app has to be built when it is missing', function (): void {
    $this->app->usePublicPath(sys_get_temp_dir().'/gm-empty-'.uniqid());

    $this->get('/student/')->assertStatus(503);
});
