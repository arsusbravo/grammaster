<?php

declare(strict_types=1);

use App\Filament\Widgets\ContentStats;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

it('sends guests to the login page', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/admin/questions')->assertRedirect('/admin/login');
});

it('keeps students out', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/admin/questions')->assertForbidden();
});

it('lets admins in and shows every section', function (string $path): void {
    $this->actingAs(User::factory()->admin()->create())->get($path)->assertOk();
})->with(['/admin', '/admin/questions', '/admin/questions/create', '/admin/topics', '/admin/levels', '/admin/exams', '/admin/exams/create', '/admin/students', '/admin/students/create', '/admin/admins', '/admin/admins/create']);

it('lets the seeded admin sign in through the login form', function (): void {
    $this->seed();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'info@arsus.nl', 'password' => 'admin1234'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs(User::where('email', 'info@arsus.nl')->first());
});

it('does not let a student sign in to the admin', function (): void {
    $this->seed();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'test@arsus.nl', 'password' => 'test1234'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

it('shows content statistics on the dashboard', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Question::factory()->count(2)->create();
    Question::factory()->unpublished()->create();
    User::factory()->count(3)->create();
    Attempt::factory()->create();

    Livewire::test(ContentStats::class)
        ->assertSee('Gepubliceerde vragen')
        ->assertSee('1 concepten')
        ->assertSee('Leerlingen');
});

describe('busy indicator', function (): void {
    it('is on every admin page, including the login page', function (string $path): void {
        $admin = User::factory()->admin()->create();

        $html = $this->actingAs($admin)->get($path)->assertOk()->getContent();

        expect($html)
            ->toContain('admin-busy-bar')
            ->toContain('Livewire.interceptRequest')
            ->toContain("button[data-saving='true']");
    })->with(['/admin', '/admin/questions/create', '/admin/students', '/admin/exams/create']);

    it('is also on the login page', function (): void {
        expect($this->get('/admin/login')->assertOk()->getContent())->toContain('admin-busy-bar');
    });

    it('does not poll on the dashboard (it would keep the loading bar blinking)', function (): void {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ContentStats::class)->assertDontSeeHtml('wire:poll');
    });
});
