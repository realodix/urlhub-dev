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
        $url_f = Url::factory()->create();
        $url_f2 = Url::factory()->create();
        $this->assertEquals(2, Url::all()->count());

        // url-id_1 - hits 1
        $this->get(route('home').'/'.$url_f->keyword);
        $this->assertEquals(1, Visit::all()->count());
        $visitor = Visit::whereUrlId($url_f->id)->first();
        $this->assertEquals(1, $visitor->hits);

        $this->get(route('home').'/'.$url_f->keyword); // url-id_1, hits +1 = 2
        $this->assertEquals(1, Visit::all()->count());
        $visitor = Visit::whereUrlId($url_f->id)->first();
        $this->assertEquals(2, $visitor->hits);

        // url-id_2 - hits 1
        $this->get(route('home').'/'.$url_f2->keyword);
        $this->assertEquals(2, Visit::all()->count());
        $visitor = Visit::whereUrlId($url_f->id)->first();
        $visitor2 = Visit::whereUrlId($url_f2->id)->first();
        $this->assertEquals(2, $visitor->hits);
        $this->assertEquals(1, $visitor2->hits);

        $this->get(route('home').'/'.$url_f2->keyword); // url-id_2, hits +1 = 2
        $visitor = Visit::whereUrlId($url_f->id)->first();
        $visitor2 = Visit::whereUrlId($url_f2->id)->first();
        $this->assertEquals(2, $visitor->hits); // url-id_1
        $this->assertEquals(2, $visitor2->hits); // url-id_2
        $this->assertEquals(4, Visit::all()->sum('hits'));
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
