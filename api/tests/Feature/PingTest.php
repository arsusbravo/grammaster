<?php

declare(strict_types=1);

it('responds on the versioned API', function (): void {
    $this->getJson('/api/v1/ping')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});
