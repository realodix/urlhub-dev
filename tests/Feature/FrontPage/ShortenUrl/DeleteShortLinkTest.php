<?php

namespace Tests\Feature\FrontPage\ShortenUrl;

use App\Models\Url;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteShortLinkTest extends TestCase
{
    #[Test]
    public function userCanDelete(): void
    {
        $url = Url::factory()->create();

        $response = $this->actingAs($url->author)
            ->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));

        $response->assertRedirectToRoute('home');
        $this->assertCount(0, Url::all());
    }

    #[Test]
    public function adminCanDeleteUrlsCreatedByOtherUsers(): void
    {
        $url = Url::factory()->create();
        $response = $this->actingAs($this->adminUser())
            ->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));

        $response->assertRedirectToRoute('home');
        $this->assertCount(0, Url::all());
    }

    #[Test]
    public function adminCanDeleteUrlsCreatedByGuest(): void
    {
        $url = Url::factory()->create(['user_id' => Url::GUEST_ID]);
        $response = $this->actingAs($this->adminUser())
            ->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));

        $response->assertRedirectToRoute('home');
        $this->assertCount(0, Url::all());
    }

    #[Test]
    public function userCannotDeleteUrlsCreatedByOtherUsers(): void
    {
        $url = Url::factory()->create();
        $response = $this->actingAs($this->normalUser())
            ->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));

        $response->assertForbidden();
        $this->assertCount(1, Url::all());
    }

    #[Test]
    public function userCannotDeleteUrlsCreatedByGuest(): void
    {
        $url = Url::factory()->create(['user_id' => Url::GUEST_ID]);
        $response = $this->actingAs($this->normalUser())
            ->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));

        $response->assertForbidden();
        $this->assertCount(1, Url::all());
    }

    #[Test]
    public function guestCannotDelete(): void
    {
        $url = Url::factory()->create(['user_id' => Url::GUEST_ID]);
        $response = $this->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));
        $response->assertForbidden();

        $url = Url::factory()->create(['user_id' => $this->adminUser()->id]);
        $response = $this->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));
        $response->assertForbidden();

        $url = Url::factory()->create();
        $response = $this->from(route('su_detail', $url->keyword))
            ->get(route('su_delete', $url->keyword));
        $response->assertForbidden();

        $this->assertCount(3, Url::all());
    }
}
