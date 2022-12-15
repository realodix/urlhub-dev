<?php

namespace Tests\Feature\API;

use Illuminate\Http\Response;
use Tests\TestCase;

class UrlTest extends TestCase
{
    /**
     * @test
     * @group f-api
     */
    public function canCreateUrl()
    {
        $data = [
            'destination' => 'http://example.com',
        ];

        $this->json('POST', '/api/url', $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'id',
                'destination',
                'short_url',
            ]);

        $this->assertDatabaseHas('urls', $data);
    }

    /**
     * @test
     * @group f-api
     * @dataProvider shortenUrlFailProvider
     *
     * @param mixed $value
     */
    public function shortenUrlFail($value)
    {
        $data = [
            'destination' => $value,
        ];

        $this->json('POST', '/api/url', $data)
            ->assertJsonStructure([
                'errors',
            ]);
    }

    public function shortenUrlFailProvider()
    {
        return [
            [''],
            ['foobar.com'],
        ];
    }
}
