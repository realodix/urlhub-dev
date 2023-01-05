<?php

namespace Tests\Unit\Services;

use App\Models\Url;
use App\Services\RandomStringService;
use Tests\TestCase;

class RandomStringServiceTest extends TestCase
{
    private Url $url;

    private RandomStringService $randomStringService;

    private const N_URL_WITH_USER_ID = 1;

    private const N_URL_WITHOUT_USER_ID = 2;

    private int $totalUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = new Url;

        $this->randomStringService = app(RandomStringService::class);

        $this->totalUrl = self::N_URL_WITH_USER_ID + self::N_URL_WITHOUT_USER_ID;
    }

    /**
     * String yang dihasilkan dengan memotong string url dari belakang sepanjang
     * panjang karakter yang telah ditentukan.
     *
     * @test
     * @group u-model
     */
    public function urlKey_default_value()
    {
        $length = 3;
        config(['urlhub.hash_length' => $length]);

        $longUrl = 'https://github.com/realodix';
        $urlKey = $this->randomStringService->urlKey($longUrl);

        $this->assertSame(substr($longUrl, -$length), $urlKey);
    }

    /**
     * Karena kunci sudah ada, maka generator akan terus diulangi hingga
     * menghasilkan kunci yang unik atau tidak ada yang sama.
     *
     * @test
     * @group u-model
     */
    public function urlKey_generated_string()
    {
        $length = 3;
        config(['urlhub.hash_length' => $length]);

        $longUrl = 'https://github.com/realodix';
        Url::factory()->create([
            'keyword'  => $this->randomStringService->urlKey($longUrl),
        ]);

        $this->assertNotSame(substr($longUrl, -$length), $this->randomStringService->urlKey($longUrl));
    }

    /**
     * Panjang dari karakter kunci yang dihasilkan harus sama dengan panjang
     * karakter yang telah ditentukan.
     *
     * @test
     * @group u-model
     */
    public function urlKey_specified_hash_length()
    {
        config(['urlhub.hash_length' => 6]);
        $actual = 'https://github.com/realodix';
        $expected = 'alodix';
        $this->assertSame($expected, $this->randomStringService->urlKey($actual));

        config(['urlhub.hash_length' => 9]);
        $actual = 'https://github.com/realodix';
        $expected = 'mrealodix';
        $this->assertSame($expected, $this->randomStringService->urlKey($actual));

        config(['urlhub.hash_length' => 12]);
        $actual = 'https://github.com/realodix';
        $expected = 'bcomrealodix';
        $this->assertSame($expected, $this->randomStringService->urlKey($actual));
    }

    /**
     * Karakter yang dihasilkan harus benar-benar mengikuti karakter yang telah
     * ditentukan.
     *
     * @test
     * @group u-model
     */
    public function urlKey_specified_character()
    {
        $url = 'https://example.com/abc';
        config(['urlhub.hash_length' => 3]);

        $this->assertSame('abc', $this->randomStringService->urlKey($url));

        config(['urlhub.hash_char' => 'xyz']);
        $this->assertMatchesRegularExpression('/[xyz]/', $this->randomStringService->urlKey($url));
        $this->assertDoesNotMatchRegularExpression('/[abc]/', $this->randomStringService->urlKey($url));

        config(['urlhub.hash_length' => 4]);
        config(['urlhub.hash_char' => 'abcm']);
        $this->assertSame('mabc', $this->randomStringService->urlKey($url));
    }

    /**
     * String yang dihasilkan tidak boleh sama dengan string yang telah ada di
     * config('urlhub.reserved_keyword')
     *
     * @test
     * @group u-model
     */
    public function urlKey_prevent_reserved_keyword()
    {
        $actual = 'https://example.com/css';
        $expected = 'css';

        config(['urlhub.reserved_keyword' => [$expected]]);
        config(['urlhub.hash_length' => strlen($expected)]);

        $this->assertNotSame($expected, $this->randomStringService->urlKey($actual));
    }

    /**
     * String yang dihasilkan tidak boleh sama dengan string yang telah ada di
     * registered route path. Di sini, string yang dihasilkan sebagai keyword
     * adalah 'admin', dimana 'admin' sudah digunakan sebagai route path.
     *
     * @test
     * @group u-model
     */
    public function urlKey_prevent_generating_strings_that_are_in_registered_route_path()
    {
        $actual = 'https://example.com/admin';
        $expected = 'admin';

        config(['urlhub.hash_length' => strlen($expected)]);

        $this->assertNotSame($expected, $this->randomStringService->urlKey($actual));
    }

    /**
     * @test
     * @group u-model
     */
    public function capacity()
    {
        $hashLength = config('urlhub.hash_length');
        $hashCharLength = strlen(config('urlhub.hash_char'));
        $capacity = pow($hashCharLength, $hashLength);

        $this->assertSame($capacity, $this->randomStringService->capacity());
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
            'keyword' => $this->randomStringService->randomString(),
        ]);
        $this->assertSame(1, $this->randomStringService->keyUsed());

        Url::factory()->create([
            'keyword'   => str_repeat('a', config('urlhub.hash_length')),
            'is_custom' => true,
        ]);
        $this->assertSame(2, $this->randomStringService->keyUsed());

        // Karena panjang karakter 'keyword' berbeda dengan dengan 'urlhub.hash_length',
        // maka ini tidak ikut terhitung.
        Url::factory()->create([
            'keyword'   => str_repeat('b', config('urlhub.hash_length') + 2),
            'is_custom' => true,
        ]);
        $this->assertSame(2, $this->randomStringService->keyUsed());

        config(['urlhub.hash_length' => config('urlhub.hash_length') + 3]);
        $this->assertSame(0, $this->randomStringService->keyUsed());
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
        $this->assertSame(1, $this->randomStringService->keyUsed());

        config(['urlhub.hash_char' => 'bar']);
        Url::factory()->create([
            'keyword'   => 'bar',
            'is_custom' => true,
        ]);
        $this->assertSame(1, $this->randomStringService->keyUsed());

        // Sudah ada 2 URL yang dibuat dengan keyword 'foo' dan 'bar', maka
        // seharusnya ada 2 saja.
        config(['urlhub.hash_char' => 'foobar']);
        $this->assertSame(2, $this->randomStringService->keyUsed());

        // Sudah ada 2 URL yang dibuat dengan keyword 'foo' dan 'bar', maka
        // seharusnya ada 1 saja karena 'bar' tidak bisa terhitung.
        config(['urlhub.hash_char' => 'fooBar']);
        $this->assertSame(1, $this->randomStringService->keyUsed());

        // Sudah ada 2 URL yang dibuat dengan keyword 'foo' dan 'bar', maka
        // seharusnya tidak ada sama sekali karena 'foo' dan 'bar' tidak
        // bisa terhitung.
        config(['urlhub.hash_char' => 'FooBar']);
        $this->assertSame(0, $this->randomStringService->keyUsed());
    }

    /**
     * @test
     * @group u-model
     * @dataProvider idleCapacityProvider
     *
     * @param mixed $kc
     * @param mixed $ku
     * @param mixed $expected
     */
    public function idleCapacity($kc, $ku, $expected)
    {
        $mock = \Mockery::mock(RandomStringService::class)->makePartial();
        $mock->shouldReceive([
            'capacity' => $kc,
            'keyUsed'     => $ku,
        ]);
        $actual = $mock->idleCapacity();

        $this->assertSame($expected, $actual);
    }

    public function idleCapacityProvider()
    {
        // capacity(), keyUsed(), expected_result
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
     * @dataProvider idleCapacityInPercentProvider
     *
     * @param mixed $kc
     * @param mixed $ku
     * @param mixed $expected
     */
    public function idleCapacityInPercent($kc, $ku, $expected)
    {
        // https://ralphjsmit.com/laravel-mock-dependencies
        $mock = \Mockery::mock(RandomStringService::class)->makePartial();
        $mock->shouldReceive([
            'capacity' => $kc,
            'keyUsed'     => $ku,
        ]);

        $actual = $mock->idleCapacityInPercent();
        $this->assertSame($expected, $actual);
    }

    public function idleCapacityInPercentProvider()
    {
        // capacity(), keyUsed(), expected_result
        return [
            [10, 10, '0%'],
            [10, 11, '0%'],
            [pow(10, 6), 999991, '0.01%'],
            [pow(10, 6), 50, '99.99%'],
            [pow(10, 6), 0, '100%'],
        ];
    }
}
