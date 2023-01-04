<?php

namespace App\Http\Controllers;

use App\Jobs\UrlRedirection;
use App\Models\Url;
use Illuminate\Support\Facades\DB;

class UrlRedirectController extends Controller
{
    /**
     * Handle the logging of the URL and redirect the user to the intended
     * long URL.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(UrlRedirection $urlRedirection, string $key)
    {
        return DB::transaction(function () use ($urlRedirection, $key) {
            $url = Url::whereKeyword($key)->firstOrFail();

            return $urlRedirection->handle($url);
        });
    }
}
