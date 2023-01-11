<?php

namespace Tests\Feature\FrontPage;

use Tests\TestCase;

class CustomKeyValidationTest extends TestCase
{
    /** @test */
    public function customKeyValidation()
    {
        $component = \Livewire\Livewire::test(\App\Http\Livewire\UrlCheck::class);

        $component->assertStatus(200)
            ->set('keyword', '!')
            ->assertHasErrors('keyword')
            ->set('keyword', 'FOO')
            ->assertHasErrors('keyword')
            ->set('keyword', 'admin')
            ->assertHasErrors('keyword')
            ->set('keyword', 'foo_bar')
            ->assertHasNoErrors('keyword');
    }
}
