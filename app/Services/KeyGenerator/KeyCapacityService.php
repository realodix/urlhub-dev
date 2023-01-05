<?php

namespace App\Services;

use App\Models\Url;

class KeyCapacityService
{
    /**
     * Calculate the maximum number of unique random strings that can be
     * generated
     */
    public function capacity(): int
    {
        $characters = strlen(config('urlhub.hash_char'));
        $length = config('urlhub.hash_length');

        // for testing purposes only
        // tests\Unit\Middleware\UrlHubLinkCheckerTest.php
        if ($length === 0) {
            return 0;
        }

        return (int) pow($characters, $length);
    }

    /**
     * The number of unique random strings that have been used as the key for
     * the long url that has been shortened
     *
     * Formula:
     * keyUsed = randomKey + customKey
     *
     * The character length and set of characters of `customKey` must be the same
     * as `randomKey`.
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
     * Calculate the number of unique random strings that can still be generated.
     */
    public function idleCapacity(): int
    {
        $capacity = $this->capacity();
        $keyUsed = $this->keyUsed();

        // max() is used to prevent negative values from being returned when the
        // keyUsed() is greater than the capacity()
        return max($capacity - $keyUsed, 0);
    }

    /**
     * Calculate the percentage of the remaining unique random strings that can
     * be generated from the total number of unique random strings that can be
     * generated (in percent) with the specified precision (in decimal places)
     * and return the result as a string.
     */
    public function idleCapacityInPercent(int $precision = 2): string
    {
        $capacity = $this->capacity();
        $remaining = $this->idleCapacity();
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
