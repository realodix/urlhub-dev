<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class UrlHubLinkCheckerTest extends TestCase
{
    /**
     * Persingkat URL ketika generator string sudah tidak dapat menghasilkan keyword
     * unik (semua keyword sudah terpakai). UrlHub harus mencegah user untuk melakukan
     * penyingkatan URL.
     */
    #[Test]
    public function remainingCapacityIsZero(): void
    {
        // MySQL 5.7 tidak memungkinkan untuk `urlhub.keyword_length` diatur ke `0`
        if (Schema::getConnection()->getConfig('driver') === 'mysql') {
            if (version_compare(DB::select('select version()')[0]->{'version()'}, '5.7')) {
                $this->markTestSkipped();
            }
        }

        config(['urlhub.keyword_length' => 0]);
        $response = $this->post(route('su_create'), ['long_url' => 'https://laravel.com']);
        $response
            ->assertRedirectToRoute('home')
            ->assertSessionHas('flash_error');
    }
}
