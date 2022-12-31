<?php

namespace App\Actions;

use App\Helpers\Helper;
use App\Http\Requests\StoreUrl;
use App\Models\Url;

class UrlShorteningAction
{
    public function __construct(
        public Url $url,
    ) {
    }

    /**
     * Handle the URL shortening and return the URL model.
     *
     * @param StoreUrl        $request \App\Http\Requests\StoreUrl
     * @param int|string|null $userId  Jika user_id tidak diisi, maka akan diisi null, ini
     *                                 terjadi karena guest yang membuat URL. See userId().
     * @return \App\Models\Url
     */
    public function handle(StoreUrl $request, $userId)
    {
        $key = $request->custom_key ?? $this->url->urlKey($request->long_url);

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
