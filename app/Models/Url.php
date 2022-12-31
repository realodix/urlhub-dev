<?php

namespace App\Models;

use App\Http\Requests\StoreUrl;
use App\Models\Traits\Hashidable;
use Embed\Embed;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Url\Url as SpatieUrl;

/**
 * @property int|null $user_id
 * @property string   $short_url
 * @property string   $destination
 * @property string   $title
 * @property int      $clicks
 * @property int      $uniqueClicks
 */
class Url extends Model
{
    use HasFactory;
    use Hashidable;

    const GUEST_ID = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'keyword',
        'is_custom',
        'destination',
        'title',
        'ip',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id'   => 'integer',
        'is_custom' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Eloquent: Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Guest',
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function visit()
    {
        return $this->hasMany(Visit::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Eloquent: Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    protected function userId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === 0 ? self::GUEST_ID : $value,
        );
    }

    protected function shortUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attr) => url('/'.$attr['keyword']),
        );
    }

    protected function destination(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => rtrim($value, '/'),
        );
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (config('urlhub.web_title')) {
                    if (Str::startsWith($value, 'http')) {
                        return $this->getWebTitle($value);
                    }

                    return $value;
                }

                return 'No Title';
            },
        );
    }

    protected function clicks(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attr) => $this->numberOfClicks($attr['id']),
        );
    }

    protected function uniqueClicks(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attr) => $this->numberOfClicks($attr['id'], unique: true),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | General Functions
    |--------------------------------------------------------------------------
    */

    /**
     * The number of shortened URLs that have been created by each User
     */
    public function numberOfUrls(int $userId): int
    {
        return self::whereUserId($userId)->count();
    }

    /**
     * The total number of shortened URLs that have been created by guests
     */
    public function numberOfUrlsByGuests(): int
    {
        return self::whereNull('user_id')->count();
    }

    /**
     * Total shortened URLs created
     */
    public function totalUrl(): int
    {
        return self::count();
    }

    /**
     * Total clicks on each shortened URLs
     */
    public function numberOfClicks(int $urlId, bool $unique = false): int
    {
        $total = self::find($urlId)->visit()->count();

        if ($unique) {
            $total = self::find($urlId)->visit()
                ->whereIsFirstClick(true)
                ->count();
        }

        return $total;
    }

    /**
     * Total clicks on all short URLs on each user
     */
    public function numberOfClicksPerUser(int $userId = null): int
    {
        $url = self::whereUserId($userId)->get();

        return $url->sum(fn ($url) => $url->numberOfClicks($url->id));
    }

    /**
     * Total clicks on all short URLs from guest users
     */
    public function numberOfClicksFromGuests(): int
    {
        $url = self::whereNull('user_id')->get();

        return $url->sum(fn ($url) => $url->numberOfClicks($url->id));
    }

    /**
     * Total clicks on all shortened URLs
     */
    public function totalClick(): int
    {
        return Visit::count();
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
     * Periksa apakah keyword tersedia atau tidak?
     *
     * Syarat keyword tersedia:
     * - Tidak ada di database
     * - Tidak ada di daftar config('urlhub.reserved_keyword')
     * - Tidak digunakan oleh sistem sebagai rute
     */
    public function keyExists(string $url): bool
    {
        $route = \Illuminate\Routing\Route::class;
        $routeCollection = \Illuminate\Support\Facades\Route::getRoutes()->get();
        $routePath = array_map(fn ($route) => $route->uri, $routeCollection);

        $isExistsInDb = self::whereKeyword($url)->first();
        $isReservedKeyword = in_array($url, config('urlhub.reserved_keyword'));
        $isRegisteredRoutePath = in_array($url, $routePath);

        if ($isExistsInDb || $isReservedKeyword || $isRegisteredRoutePath) {
            return true;
        }

        return false;
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

        $randomKey = self::whereIsCustom(false)
            ->whereRaw('LENGTH(keyword) = ?', [$hashLength])
            ->count();

        $customKey = self::whereIsCustom(true)
            ->whereRaw('LENGTH(keyword) = ?', [$hashLength])
            ->whereRaw("keyword REGEXP '".$regexPattern."'")
            ->count();

        return $randomKey + $customKey;
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

    /**
     * Fetch the page title from the web page URL
     *
     * @throws \Exception
     */
    public function getWebTitle(string $url): string
    {
        $spatieUrl = SpatieUrl::fromString($url);
        $defaultTitle = $spatieUrl->getHost().' - Untitled';

        try {
            $webTitle = (new Embed)->get($url)->title ?? $defaultTitle;
        } catch (\Exception) {
            // If failed or not found, then return "{domain_name} - Untitled"
            $webTitle = $defaultTitle;
        }

        return $webTitle;
    }

    /*
    |--------------------------------------------------------------------------
    | Ada di Action class, tapi belum benerin di Tests
    |--------------------------------------------------------------------------
    */

    /**
     * \tests\Feature\ShortenUrlTest
     *
     * @param StoreUrl        $request \App\Http\Requests\StoreUrl
     * @param int|string|null $userId  Jika user_id tidak diisi, maka akan diisi null, ini
     *                                 terjadi karena guest yang membuat URL. See userId().
     * @return self
     */
    public function shortenUrl(StoreUrl $request, $userId)
    {
        $action = new \App\Actions\Url\ShortenUrl($this);

        return $action->handle($request, $userId);
    }

    /**
     * @param int|string|null $userId \Illuminate\Contracts\Auth\Guard::id()
     * @return bool \Illuminate\Database\Eloquent\Model::save()
     */
    public function duplicate(string $key, $userId, string $randomKey = null)
    {
        $action = new \App\Actions\Url\DuplicateUrl($this);

        return $action->handle($key, $userId, $randomKey);
    }

    /**
     * @return string
     */
    public function randomString()
    {
        $action = new \App\Actions\UrlKey\GenerateString($this);

        return $action->handle();
    }
}
