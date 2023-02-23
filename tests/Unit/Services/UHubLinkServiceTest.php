<?php

namespace Tests\Unit\Services;

use App\Services\UHubLinkService;
use Tests\TestCase;

class UHubLinkServiceTest extends TestCase
{
    private UHubLinkService $uHubLinkService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uHubLinkService = app(UHubLinkService::class);
    }

    /** @test */
    public function title(): void
    {
        $expected = 'example123456789.com - Untitled';
        $actual = $this->uHubLinkService->title('https://example123456789.com');
        $this->assertSame($expected, $actual);

        $expected = 'www.example123456789.com - Untitled';
        $actual = $this->uHubLinkService->title('https://www.example123456789.com');
        $this->assertSame($expected, $actual);
    }

    /**
     * title() should return 'no title' if the title is empty and
     * config('urlhub.web_title') set `false`.
     *
     * @test
     */
    public function titleShouldReturnNoTitle(): void
    {
        config(['urlhub.web_title' => false]);

        $expected = 'No Title';
        $actual = $this->uHubLinkService->title('https://example123456789.com', '');
        $this->assertSame($expected, $actual);
    }
}
