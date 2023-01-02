<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Url;
use App\Models\Visit;

class KeyGeneratorService
{
    public function __construct(
        public Visit $visit,
    ) {
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
        if ($this->keyExists($urlKey)) {
            $urlKey = $this->randomString();
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
