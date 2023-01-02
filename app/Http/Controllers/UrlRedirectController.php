<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Services\UrlRedirectionService;
use Illuminate\Support\Facades\DB;

class UrlRedirectController extends Controller
{
    /**
     * Handle the logging of the URL and redirect the user to the intended
     * long URL.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(UrlRedirectionService $action, string $key)
    {
        return DB::transaction(function () use ($action, $key) {
            $url = Url::whereKeyword($key)->firstOrFail();

            return $action->handleHttpRedirect($url);
        });
    }
}
