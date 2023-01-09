<?php

namespace App\Services;

use App\Models\Url;

class DuplicateUrl
{
    /**
     * @param string          $urlKey A unique key for the shortened URL
     * @param int|string|null $userId \Illuminate\Contracts\Auth\Guard::id()
     * @return bool \Illuminate\Database\Eloquent\Model::save()
     */
    public function execute(string $urlKey, $userId, string $randomKey = null)
    {
        $randomKey = $randomKey ?? app(KeyGeneratorService::class)->generateRandomString();
        $shortenedUrl = Url::whereKeyword($urlKey)->first();

        $replicate = $shortenedUrl->replicate()->fill([
            'user_id'   => $userId,
            'keyword'   => $randomKey,
            'is_custom' => false,
        ]);

        return $replicate->save();
    }
}
