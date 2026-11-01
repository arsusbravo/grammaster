<?php

declare(strict_types=1);

use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;

it('lists levels in order without authentication', function (): void {
    Level::factory()->create(['slug' => 'vwo', 'name' => 'VWO', 'sort_order' => 2]);
    Level::factory()->create(['slug' => 'vmbo', 'name' => 'VMBO', 'sort_order' => 1]);

    $this->getJson('/api/v1/levels')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'vmbo')
        ->assertJsonPath('data.1.slug', 'vwo')
        ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'sort_order']]]);
});

it('lists topics in order with published question counts', function (): void {
    $first = Topic::factory()->create(['sort_order' => 1]);
    $second = Topic::factory()->create(['sort_order' => 2]);
    Question::factory()->count(2)->create(['topic_id' => $first->id]);
    Question::factory()->unpublished()->create(['topic_id' => $first->id]);

    $this->getJson('/api/v1/topics')
        ->assertOk()
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.0.questions_count', 2)
        ->assertJsonPath('data.1.id', $second->id)
        ->assertJsonPath('data.1.questions_count', 0)
        ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'description', 'sort_order', 'questions_count']]]);
});

it('limits topic counts to a level', function (): void {
    $topic = Topic::factory()->create();
    $vmbo = Level::factory()->create(['slug' => 'vmbo']);
    Question::factory()->create(['topic_id' => $topic->id, 'level_id' => $vmbo->id]);
    Question::factory()->count(2)->create(['topic_id' => $topic->id]);

    $this->getJson('/api/v1/topics?level=vmbo')->assertJsonPath('data.0.questions_count', 1);
    $this->getJson('/api/v1/topics')->assertJsonPath('data.0.questions_count', 3);
});
