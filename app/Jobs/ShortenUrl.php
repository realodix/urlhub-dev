<?php

namespace App\Jobs;

use App\Helpers\Helper;
use App\Http\Requests\StoreUrl;
use App\Models\Url;
use App\Services\UrlKeyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShortenUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public UrlKeyService $urlKeyService,
    ) {
        //
    }

    /**
     * @param StoreUrl        $request \App\Http\Requests\StoreUrl
     * @param int|string|null $userId  Jika user_id tidak diisi, maka akan diisi null, ini
     *                                 terjadi karena guest yang membuat URL. See userId().
     * @return \App\Models\Url
     */
    public function handle(StoreUrl $request, $userId)
    {
        $key = $request->custom_key ?? $this->urlKeyService->urlKey($request->long_url);

        return Url::create([
            'user_id'     => $userId,
            'destination' => $request->long_url,
            'title'       => $request->long_url,
            'keyword'     => $key,
            'is_custom'   => $request->custom_key ? true : false,
            'ip'          => Helper::anonymizeIp($request->ip()),
        ]);
    }
}
