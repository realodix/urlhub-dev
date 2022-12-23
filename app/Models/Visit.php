<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Visit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'url_id',
        'user_id',
        'visitor_id',
        'hits',
        'referer',
        'ip',
        'browser',
        'browser_version',
        'device',
        'os',
        'os_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'hits' => 1,
    ];

    /*
    |--------------------------------------------------------------------------
    | Eloquent: Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function url()
    {
        return $this->belongsTo(Url::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Other Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Generate unique Visitor Id
     */
    public function visitorId(int $urlId): string
    {
        $ua = Request::userAgent();
        $referer = Request::header('referer');
        $visitorId = $urlId.'_'.Request::ip().'_'.$ua.$referer;

        if (Auth::check() === true) {
            $visitorId = $urlId.'_'.Auth::id().'_'.$ua.$referer;
        }

        return hash('sha3-256', $visitorId);
    }

    /**
     * total visit
     */
    public function totalClick(): int
    {
        return self::sum('hits');
    }

    /**
     * Total visit by user id
     */
    public function totalClickPerUser(int $userId = null): int
    {
        return self::whereUserId($userId)->sum('hits');
    }

    /**
     * Total visit by URL id
     */
    public function totalClickPerUrl(int $urlId, bool $unique = false): int
    {
        $total = self::whereUrlId($urlId)
            ->sum('hits');

        if ($unique) {
            $total = self::whereUrlId($urlId)->count();
        }

        return $total;
    }
}
