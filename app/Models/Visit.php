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
        $visitorId = $urlId.'_'.Request::ip().'_'.Request::header('user-agent');

        if (Auth::check() === true) {
            $visitorId = $urlId.'_'.Auth::id();
        }

        return hash('sha3-256', $visitorId);
    }
}
