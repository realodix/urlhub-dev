<?php

namespace Tests\Feature\FrontPage;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    #[Test]
    public function createShortUrlWithWrongUrlFormat(): void
    {
        $response = $this->post(route('su_create'), [
            'long_url' => 'wrong-url-format',
        ]);

        $response
            ->assertRedirectToRoute('home')
            ->assertSessionHasErrors('long_url');
    }

    #[Test]
    public function customKeyValidation(): void
    {
        $component = \Livewire\Livewire::test(\App\Livewire\UrlCheck::class);

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
