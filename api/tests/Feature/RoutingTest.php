<?php

declare(strict_types=1);

it('sends the root address to the student app', function (): void {
    $this->get('/')->assertRedirect('/student/');
});

it('keeps the admin and the API on their own paths', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->getJson('/api/v1/ping')->assertOk()->assertExactJson(['status' => 'ok']);
});
