<?php

namespace App\Services;

use App\Models\Url;

class UrlKeyService
{
    public function __construct(
        public Url $url,
    ) {
        //
    }

    public function urlKey(string $url): string
    {
        $length = config('urlhub.hash_length') * -1;

        // Step 1
        // Take a few characters at the end of the string to use as a unique key
        $pattern = '/[^'.config('urlhub.hash_char').']/i';
        $urlKey = substr(preg_replace($pattern, '', $url), $length);

        // Step 2
        // If step 1 fails (the already used or cannot be used), then the generator
        // must generate a unique random string
        if ($this->url->keyExists($urlKey)) {
            $urlKey = $this->url->randomString();
        }

        return $urlKey;
    }

    /**
     * Calculate the maximum number of unique random strings that can be
     * generated
     */
    public function keyCapacity(): int
    {
        $alphabet = strlen(config('urlhub.hash_char'));
        $length = config('urlhub.hash_length');

        // for testing purposes only
        // tests\Unit\Middleware\UrlHubLinkCheckerTest.php
        if ($length === 0) {
            return 0;
        }

        return (int) pow($alphabet, $length);
    }

    /**
     * The number of unique random strings that have been used as the key for
     * the long url that has been shortened
     *
     * Formula:
     * keyUsed = randomKey + customKey
     *
     * The character length of the generated for `customKey` should be similar
     * to `randomKey`
     */
    public function keyUsed(): int
    {
        $hashLength = (int) config('urlhub.hash_length');
        $regexPattern = '['.config('urlhub.hash_char').']{'.$hashLength.'}';

        $randomKey = Url::whereIsCustom(false)
            ->whereRaw('LENGTH(keyword) = ?', [$hashLength])
            ->count();

        $customKey = Url::whereIsCustom(true)
            ->whereRaw('LENGTH(keyword) = ?', [$hashLength])
            ->whereRaw("keyword REGEXP '".$regexPattern."'")
            ->count();

        return $randomKey + $customKey;
    }

    /**
     * Count unique random strings that can be generated
     */
    public function keyRemaining(): int
    {
        $keyCapacity = $this->keyCapacity();
        $keyUsed = $this->keyUsed();

        return max($keyCapacity - $keyUsed, 0);
    }

    public function keyRemainingInPercent(int $precision = 2): string
    {
        $capacity = $this->keyCapacity();
        $remaining = $this->keyRemaining();
        $result = round(($remaining / $capacity) * 100, $precision);

        $lowerBoundInPercent = 1 / (10 ** $precision);
        $upperBoundInPercent = 100 - $lowerBoundInPercent;
        $lowerBound = $lowerBoundInPercent / 100;
        $upperBound = 1 - $lowerBound;

        if ($remaining > 0 && $remaining < ($capacity * $lowerBound)) {
            $result = $lowerBoundInPercent;
        } elseif (($remaining > ($capacity * $upperBound)) && ($remaining < $capacity)) {
            $result = $upperBoundInPercent;
        }

        return $result.'%';
    }
}
