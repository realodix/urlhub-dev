<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUrl;
use App\Jobs\ShortenUrl;
use App\Jobs\UrlDuplication;
use App\Models\Url;
use App\Services\QrCodeService;

class UrlController extends Controller
{
    /**
     * UrlController constructor.
     */
    public function __construct(
        public Url $url,
        public QrCodeService $qrCodeService,
        public ShortenUrl $shortenUrl,
        public UrlDuplication $urlDuplication,
    ) {
        $this->middleware('urlhublinkchecker')->only('create');
    }

    /**
     * Shorten long URLs.
     *
     * @param StoreUrl $request \App\Http\Requests\StoreUrl
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(StoreUrl $request)
    {
        $url = $this->shortenUrl->handle($request, auth()->id());

        return to_route('su_detail', $url->keyword);
    }

    /**
     * View the shortened URL details.
     *
     * @codeCoverageIgnore
     *
     * @param string $key
     * @return \Illuminate\Contracts\View\View
     */
    public function showDetail($key)
    {
        $url = Url::with('visit')->whereKeyword($key)->firstOrFail();
        $data = ['url' => $url, 'visit' => new \App\Models\Visit];

        if (config('urlhub.qrcode')) {
            $qrCode = $this->qrCodeService->execute($url->short_url);

            $data = array_merge($data, ['qrCode' => $qrCode]);
        }

        return view('frontend.short', $data);
    }

    /**
     * Delete a shortened URL on user request.
     *
     * @param mixed $url
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete($url)
    {
        $this->authorize('forceDelete', $url);

        $url->delete();

        return to_route('home');
    }

    /**
     * UrlHub only allows users (registered & unregistered) to have a unique
     * link. You can duplicate it and it will generated a new unique random
     * key.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(string $key)
    {
        $randomKey = $this->url->randomString();
        $this->urlDuplication->handle($key, auth()->id(), $randomKey);

        return to_route('su_detail', $randomKey)
            ->withFlashSuccess(__('The link has successfully duplicated.'));
    }
}
