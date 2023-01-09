<?php

namespace App\Services;

use App\Http\Requests\StoreUrl;
use App\Models\Url;
use Illuminate\Http\Request;

class UHubLink
{
    public function __construct(
        public KeyGeneratorService $keyGeneratorService,
    ) {
    }

    public function create(StoreUrl $request): Url
    {
        return Url::create([
            'user_id'     => auth()->id(),
            'destination' => $request->long_url,
            'title'       => $request->long_url,
            'keyword'     => $this->urlKey($request),
            'is_custom'   => $this->isCustom($request),
            'ip'          => $request->ip(),
        ]);
    }

    /**
     * @return bool
     */
    public function update(Request $request, Url $url)
    {
        return $url->update([
            'destination' => $request->long_url,
            'title' => $request->title,
        ]);
    }

    /**
     * @param int|string|null $userId \Illuminate\Contracts\Auth\Guard::id()
     * @return bool \Illuminate\Database\Eloquent\Model::save()
     */
    public function duplicate(string $urlKey, $userId, string $randomKey = null)
    {
        $randomKey = $randomKey ?? $this->keyGeneratorService->generateRandomString();
        $shortenedUrl = Url::whereKeyword($urlKey)->first();

        $replicate = $shortenedUrl->replicate()->fill([
            'user_id'   => $userId,
            'keyword'   => $randomKey,
            'is_custom' => false,
        ]);

        return $replicate->save();
    }

    private function urlKey(StoreUrl $request): string
    {
        return $request->custom_key ??
            $this->keyGeneratorService->urlKey($request->long_url);
    }

    private function isCustom(StoreUrl $request): bool
    {
        return $request->custom_key ? true : false;
    }
}
