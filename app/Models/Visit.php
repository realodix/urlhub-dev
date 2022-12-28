<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'url_author_id',
        'visitor_id',
        'is_first_click',
        'referer',
        'ip',
        'browser',
        'browser_version',
        'device',
        'os',
        'os_version',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_first_click' => 'boolean',
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
    | General Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Generate unique Visitor Id
     */
    public function visitorId(): string
    {
        $neighborVisitor = [
            'ip'      => request()->ip(),
            'browser' => \Browser::browserFamily(),
            'os'      => \Browser::platformFamily(),
        ];
        $visitorId = hash('sha3-256', implode($neighborVisitor));

        if (auth()->check() === true) {
            $visitorId = (string) auth()->id();
        }

        return $visitorId;
    }

    public function isFirstClick(Url $url): bool
    {
        $hasVisited = Visit::whereVisitorId($this->visitorId())
            ->whereUrlId($url->id)
            ->first();

        return $hasVisited ? false : true;
    }

    /**
     * Total visit by user id
     */
    public function totalClickPerUser(int $authorId = null): int
    {
        return self::whereUrlAuthorId($authorId)->count();
    }
}
