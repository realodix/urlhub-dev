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
        $url = Url::factory()->create();
        $visit = Visit::factory()->create(['url_id' => $url->id]);
        $visitor = Visit::whereVisitorId($visit->visitor_id);

        $this->get(route('home').'/'.$url->keyword);
        $this->assertEquals(1, $visitor->sum('hits'));

        $this->get(route('home').'/'.$url->keyword);
        $this->assertEquals(1, $visitor->count());
        $this->assertEquals(2, $visitor->sum('hits'));
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
