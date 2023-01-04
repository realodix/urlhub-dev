<?php

namespace App\Models;

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

    /**
     * @param int|string|null $userId \Illuminate\Contracts\Auth\Guard::id()
     * @return bool \Illuminate\Database\Eloquent\Model::save()
     */
    public function duplicate(string $key, $userId, string $randomKey = null)
    {
        $randomKey = $randomKey ?? $this->randomString();
        $shortenedUrl = self::whereKeyword($key)->firstOrFail();

        $replicate = $shortenedUrl->replicate()->fill([
            'user_id'   => $userId,
            'keyword'   => $randomKey,
            'is_custom' => false,
        ]);

        return $replicate->save();
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

        $isExistsInDb = Url::whereKeyword($url)->first();
        $isReservedKeyword = in_array($url, config('urlhub.reserved_keyword'));
        $isRegisteredRoutePath = in_array($url, $routePath);

        if ($isExistsInDb || $isReservedKeyword || $isRegisteredRoutePath) {
            return true;
        }

        return false;
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

    /**
     * @return string
     */
    public function randomString()
    {
        $factory = new \RandomLib\Factory;
        $generator = $factory->getMediumStrengthGenerator();

        $character = config('urlhub.hash_char');
        $length = config('urlhub.hash_length');

        do {
            $urlKey = $generator->generateString($length, $character);
        } while ($this->keyExists($urlKey));

        return $urlKey;
    }
}
