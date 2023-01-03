<?php

namespace Tests\Unit\Services;

use App\Models\Url;
use App\Services\UrlKeyService;
use Tests\TestCase;

class UrlKeyServiceTest extends TestCase
{
    private Url $url;

    private UrlKeyService $urlKeyService;

    private const N_URL_WITH_USER_ID = 1;

    private const N_URL_WITHOUT_USER_ID = 2;

    private int $totalUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = new Url;

        $this->urlKeyService = new UrlKeyService;

        $this->totalUrl = self::N_URL_WITH_USER_ID + self::N_URL_WITHOUT_USER_ID;
    }

    /**
     * @test
     * @group u-model
     */
    public function keyCapacity()
    {
        $hashLength = config('urlhub.hash_length');
        $hashCharLength = strlen(config('urlhub.hash_char'));
        $keyCapacity = pow($hashCharLength, $hashLength);

        $this->assertSame($keyCapacity, $this->urlKeyService->keyCapacity());
    }

    /**
     * Pengujian dilakukan berdasarkan panjang karakternya.
     *
     * @test
     * @group u-model
     */
    public function keyUsed()
    {
        config(['urlhub.hash_length' => config('urlhub.hash_length') + 1]);

        Url::factory()->create([
            'keyword' => $this->url->randomString(),
        ]);
        $this->assertSame(1, $this->urlKeyService->keyUsed());

        Url::factory()->create([
            'keyword'   => str_repeat('a', config('urlhub.hash_length')),
            'is_custom' => true,
        ]);
        $this->assertSame(2, $this->urlKeyService->keyUsed());

        // Karena panjang karakter 'keyword' berbeda dengan dengan 'urlhub.hash_length',
        // maka ini tidak ikut terhitung.
        Url::factory()->create([
            'keyword'   => str_repeat('b', config('urlhub.hash_length') + 2),
            'is_custom' => true,
        ]);
        $this->assertSame(2, $this->urlKeyService->keyUsed());

        config(['urlhub.hash_length' => config('urlhub.hash_length') + 3]);
        $this->assertSame(0, $this->urlKeyService->keyUsed());
        $this->assertSame($this->totalUrl, $this->url->totalUrl());
    }

    /**
     * Pengujian dilakukan berdasarkan karakter yang telah ditetapkan pada
     * 'urlhub.hash_char'. Jika salah satu karakter 'keyword' tidak ada di
     * 'urlhub.hash_char', maka seharusnya ini tidak dapat dihitung.
     *
     * @test
     * @group u-model
     */
    public function keyUsed2()
    {
        config(['urlhub.hash_length' => 3]);

        config(['urlhub.hash_char' => 'foo']);
        Url::factory()->create([
            'keyword'   => 'foo',
            'is_custom' => true,
        ]);
        $this->assertSame(1, $this->urlKeyService->keyUsed());

        config(['urlhub.hash_char' => 'bar']);
        Url::factory()->create([
            'keyword'   => 'bar',
            'is_custom' => true,
        ]);
        $this->assertSame(1, $this->urlKeyService->keyUsed());

        // Sudah ada 2 URL yang dibuat dengan keyword 'foo' dan 'bar', maka
        // seharusnya ada 2 saja.
        config(['urlhub.hash_char' => 'foobar']);
        $this->assertSame(2, $this->urlKeyService->keyUsed());

        // Sudah ada 2 URL yang dibuat dengan keyword 'foo' dan 'bar', maka
        // seharusnya ada 1 saja karena 'bar' tidak bisa terhitung.
        config(['urlhub.hash_char' => 'fooBar']);
        $this->assertSame(1, $this->urlKeyService->keyUsed());

        // Sudah ada 2 URL yang dibuat dengan keyword 'foo' dan 'bar', maka
        // seharusnya tidak ada sama sekali karena 'foo' dan 'bar' tidak
        // bisa terhitung.
        config(['urlhub.hash_char' => 'FooBar']);
        $this->assertSame(0, $this->urlKeyService->keyUsed());
    }

    /**
     * @test
     * @group u-model
     * @dataProvider keyRemainingProvider
     *
     * @param mixed $kc
     * @param mixed $ku
     * @param mixed $expected
     */
    public function keyRemaining($kc, $ku, $expected)
    {
        $mock = \Mockery::mock(UrlKeyService::class)->makePartial();
        $mock->shouldReceive([
            'keyCapacity' => $kc,
            'keyUsed'     => $ku,
        ]);
        $actual = $mock->keyRemaining();

        $this->assertSame($expected, $actual);
    }

    public function keyRemainingProvider()
    {
        // keyCapacity(), keyUsed(), expected_result
        return [
            [1, 2, 0],
            [3, 2, 1],
            [100, 99, 1],
            [100, 20, 80],
            [100, 100, 0],
        ];
    }

    /**
     * @test
     * @group u-model
     * @dataProvider keyRemainingInPercentProvider
     *
     * @param mixed $kc
     * @param mixed $ku
     * @param mixed $expected
     */
    public function keyRemainingInPercent($kc, $ku, $expected)
    {
        // https://ralphjsmit.com/laravel-mock-dependencies
        $mock = \Mockery::mock(UrlKeyService::class)->makePartial();
        $mock->shouldReceive([
            'keyCapacity' => $kc,
            'keyUsed'     => $ku,
        ]);

        $actual = $mock->keyRemainingInPercent();
        $this->assertSame($expected, $actual);
    }

    public function keyRemainingInPercentProvider()
    {
        // keyCapacity(), keyUsed(), expected_result
        return [
            [10, 10, '0%'],
            [10, 11, '0%'],
            [pow(10, 6), 999991, '0.01%'],
            [pow(10, 6), 50, '99.99%'],
            [pow(10, 6), 0, '100%'],
        ];
    }
}
