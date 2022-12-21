<?php

namespace Tests\Feature;

use App\Models\Url;
use App\Models\Visit;
use Tests\TestCase;

class VisitTest extends TestCase
{
    const BOT_UA = 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)';

    /** @test */
    public function visitorHits()
    {
        $urlFactory = Url::factory()->create();
        $urlFactory2 = Url::factory()->create();
        $this->assertEquals(2, Url::all()->count());

        // URL ID 1
        $this->get(route('home').'/'.$urlFactory->keyword); // Buat baru untuk Url ID 1
        $this->assertEquals(1, Visit::all()->count());
        $visitor = Visit::whereUrlId($urlFactory->id)->first();
        $this->assertEquals(1, $visitor->hits);

        $this->get(route('home').'/'.$urlFactory->keyword); // Penambahan +1 untuk Url ID 1
        $this->assertEquals(1, Visit::all()->count());
        $visitor = Visit::whereUrlId($urlFactory->id)->first();
        $this->assertEquals(2, $visitor->hits);

        // URL ID 2
        $this->get(route('home').'/'.$urlFactory2->keyword); // Buat baru
        $this->assertEquals(2, Visit::all()->count());
        $visitor = Visit::whereUrlId($urlFactory->id)->first();
        $visitor2 = Visit::whereUrlId($urlFactory2->id)->first();
        $this->assertEquals(2, $visitor->hits);
        $this->assertEquals(1, $visitor2->hits);

        $this->get(route('home').'/'.$urlFactory2->keyword); // Penambahan +1
        $visitor = Visit::whereUrlId($urlFactory->id)->first();
        $visitor2 = Visit::whereUrlId($urlFactory2->id)->first();
        $this->assertEquals(3, $visitor->hits); // Harusnya 2, tapi malah 3
        $this->assertEquals(2, $visitor2->hits);
        $this->assertEquals(5, Visit::all()->sum('hits')); // Harusnya 4, tapi malah 5
    }

    /** @test */
    public function logBotVisits()
    {
        config(['urlhub.log_bot_visit' => true]);

        $url = Url::factory()->create();

        $this->withHeaders(['user-agent' => self::BOT_UA])
            ->get(route('home').'/'.$url->keyword);
        $this->assertCount(1, Visit::all());
    }

    /** @test */
    public function dontLogBotVisits()
    {
        config(['urlhub.log_bot_visit' => false]);

        $url = Url::factory()->create();

        $this->withHeaders(['user-agent' => self::BOT_UA])
            ->get(route('home').'/'.$url->keyword);
        $this->assertCount(0, Visit::all());
    }
}
