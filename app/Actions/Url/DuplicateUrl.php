<?php

namespace App\Actions\Url;

use App\Actions\UrlKey\GenerateString;
use App\Models\Url;

class DuplicateUrl
{
    public function __construct(
        public Url $url,
        public GenerateString $generateString,
    ) {
    }

    /**
     * Handle the duplication of a Shortened URL.
     *
     * @param int|string|null $userId \Illuminate\Contracts\Auth\Guard::id()
     * @return bool \Illuminate\Database\Eloquent\Model::save()
     */
    public function handle(string $key, $userId, string $randomKey = null)
    {
        $randomKey = $randomKey ?? $this->generateString->handle();
        $shortenedUrl = $this->url->whereKeyword($key)->firstOrFail();

        $replicate = $shortenedUrl->replicate()->fill([
            'user_id'   => $userId,
            'keyword'   => $randomKey,
            'is_custom' => false,
        ]);

        return $replicate->save();
    }
}
