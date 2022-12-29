<?php

namespace Tests\Unit\Models;

use App\Models\Url;
use App\Models\Visit;
use Tests\TestCase;

class VisitTest extends TestCase
{
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visit = new Visit;
    }

    /**
     * @test
     * @group u-model
     */
    public function belongsToUrl()
    {
        $visit = Visit::factory()->create([
            'url_id' => fn () => Url::factory()->create()->id,
        ]);

        $this->assertTrue($visit->url()->exists());
    }


}
