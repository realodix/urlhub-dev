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
        $visitorId = (string) auth()->id();

        if ($this->isAnonymousVisitors()) {
            $neighborVisitor = [
                'ip'      => request()->ip(),
                'browser' => \Browser::browserFamily(),
                'os'      => \Browser::platformFamily(),
            ];

            $visitorId = sha1(implode($neighborVisitor));
        }

        return $visitorId;
    }

    public function isAnonymousVisitors(): bool
    {
        return auth()->check() === false;
    }

    /**
     * Check if the visitor is an anonymous (unauthenticated) visitor.
     */
    public function isAnonymousVisitors(): bool
    {
        return auth()->check() === false;
    }

    /**
     * Check if the visitor has clicked the link before. If the visitor has not
     * clicked the link before, return true.
     */
    public function isFirstClick(Url $url): bool
    {
        $hasVisited = self::whereUrlId($url->id)
            ->whereVisitorId($this->visitorId())
            ->exists();

        return $hasVisited ? false : true;
    }
}
