<?php

namespace App\Jobs;

use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UrlDuplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Url $url,
    ) {
        //
    }

    /**
     * @param int|string|null $userId \Illuminate\Contracts\Auth\Guard::id()
     * @return bool \Illuminate\Database\Eloquent\Model::save()
     */
    public function handle(string $key, $userId, string $randomKey = null)
    {
        $randomKey = $randomKey ?? $this->url->randomString();
        $shortenedUrl = Url::whereKeyword($key)->firstOrFail();

        $replicate = $shortenedUrl->replicate()->fill([
            'user_id'   => $userId,
            'keyword'   => $randomKey,
            'is_custom' => false,
        ]);

        return $replicate->save();
    }
}
