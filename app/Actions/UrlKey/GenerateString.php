<?php

namespace App\Actions\UrlKey;

use App\Models\Url;

class GenerateString
{
    public function __construct(
        public Url $url,
    ) {
    }

    /**
     * @return string
     */
    public function handle()
    {
        $factory = new \RandomLib\Factory;
        $generator = $factory->getMediumStrengthGenerator();

        $character = config('urlhub.hash_char');
        $length = config('urlhub.hash_length');

        do {
            $urlKey = $generator->generateString($length, $character);
        } while ($this->url->keyExists($urlKey));

        return $urlKey;
    }
}
