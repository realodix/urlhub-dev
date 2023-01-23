<?php

namespace Tests\Unit\Models;

use App\Models\Url;
use App\Models\User;
use App\Models\Visit;
use Tests\TestCase;

class UrlTest extends TestCase
{
    private const N_URL_WITH_USER_ID = 1;

    private const N_URL_WITHOUT_USER_ID = 2;

    private Url $url;

    private int $totalUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = new Url;

        $this->totalUrl = self::N_URL_WITH_USER_ID + self::N_URL_WITHOUT_USER_ID;

        Url::factory(self::N_URL_WITH_USER_ID)->create([
            'user_id' => $this->adminUser()->id,
        ]);

        Url::factory(self::N_URL_WITHOUT_USER_ID)->create([
            'user_id' => Url::GUEST_ID,
        ]);
    }

    /**
     * Url model must have a relationship with User model as one to many.
     * This test will check if the relationship exists.
     *
     * @test
     * @group u-model
     */
    public function belongsToUserModel()
    {
        $url = Url::factory()->create();

        $this->assertEquals(2, $url->author->count());
        $this->assertInstanceOf(User::class, $url->author);
    }

    /**
     * Url model must have a relationship with Visit model as one to many.
     * This test will check if the relationship exists.
     *
     * @test
     * @group u-model
     */
    public function hasManyUrlModel()
    {
        $url = Url::factory()->create();

        Visit::factory()->create([
            'url_id' => $url->id,
        ]);

        $this->assertTrue($url->visits()->exists());
    }

    /**
     * The default guest name must be Guest.
     *
     * @test
     * @group u-model
     */
    public function defaultGuestName()
    {
        $url = Url::factory()->create([
            'user_id' => Url::GUEST_ID,
        ]);

        $this->assertSame('Guest', $url->author->name);
    }

    /**
     * The default guest id must be null.
     *
     * @test
     * @group u-model
     */
    public function defaultGuestId()
    {
        $longUrl = 'https://example.com';

        $this->post(route('su_create'), [
            'long_url' => $longUrl,
        ]);

        $url = Url::whereDestination($longUrl)->first();

        $this->assertSame(null, $url->user_id);
    }

    /**
     * @test
     * @group u-model
     */
    public function setUserIdAttributeMustBeNull()
    {
        $url = Url::factory()->create([
            'user_id' => 0,
        ]);

        $this->assertEquals(null, $url->user_id);
    }

    /**
     * @test
     * @group u-model
     */
    public function setLongUrlAttribute()
    {
        $url = Url::factory()->create([
            'destination' => 'http://example.com/',
        ]);

        $expected = $url->destination;
        $actual = 'http://example.com';
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function getShortUrlAttribute()
    {
        $urlModel = Url::factory()->create();
        $url = Url::whereUserId($urlModel->author->id)->first();

        $expected = $url->short_url;
        $actual = url('/'.$url->keyword);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function setMetaTitleAttributeWhenWebTitleSetToFalse()
    {
        config()->set('urlhub.web_title', false);

        $url = Url::factory()->create([
            'destination' => 'http://example.com/',
        ]);

        $this->assertSame('No Title', $url->title);
    }

    /**
     * @test
     * @group u-model
     */
    public function totalShortUrl()
    {
        $expected = $this->totalUrl;
        $actual = $this->url->totalUrl();

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function totalShortUrlByMe()
    {
        $urlModel = Url::factory()->create();
        $actual = $this->url->numberOfUrls($urlModel->author->id);

        $this->assertSame(1, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function totalShortUrlByGuest()
    {
        Url::factory()->create([
            'user_id' => Url::GUEST_ID,
        ]);
        $actual = $this->url->numberOfUrlsByGuests();

        $this->assertSame(3, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function totalClicks()
    {
        Visit::factory()->create();

        $url = new Url;

        $expected = 1;
        $actual = $url->totalClick();

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function numberOfClicks()
    {
        Visit::factory()->create([
            'url_id' => 1,
            'is_first_click' => true,
        ]);

        Visit::factory()->create([
            'url_id' => 1,
            'is_first_click' => false,
        ]);

        $expected = 2;
        $actual = $this->url->numberOfClicks(1);

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @group u-model
     */
    public function numberOfClicksAndUnique()
    {
        Visit::factory()->create([
            'url_id' => 1,
            'is_first_click' => true,
        ]);

        Visit::factory()->create([
            'url_id' => 1,
            'is_first_click' => false,
        ]);

        $expected = 1;
        $actual = $this->url->numberOfClicks(1, unique: true);

        $this->assertSame($expected, $actual);
    }

    /**
     * Total klik dari setiap shortened URLs yang dibuat oleh user tertentu
     *
     * @test
     * @group u-model
     */
    public function numberOfClicksPerAuthor()
    {
        $visit = Visit::factory()->create();

        $expected = Visit::whereUrlId($visit->url->id)->count();
        $actual = $this->url->numberOfClicksPerAuthor(userId: $visit->url->user_id);

        $this->assertSame($expected, $actual);
    }

    /**
     * Total klik dari setiap shortened URLs yang dibuat oleh guest user
     *
     * @test
     * @group u-model
     */
    public function numberOfClicksFromGuests()
    {
        $visit = Visit::factory()
            ->for(
                Url::factory()->create([
                    'user_id' => Url::GUEST_ID,
                ])
            )
            ->create();

        $expected = Visit::whereUrlId($visit->url->id)->count();
        $actual = $this->url->numberOfClicksFromGuests();

        $this->assertSame(Url::GUEST_ID, $visit->url->user_id);
        $this->assertSame($expected, $actual);
    }
}
