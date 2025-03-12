<?php

namespace App\Http\Middleware;

use App\Services\KeyGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;

class UrlHubLinkChecker
{
    /**
     * Handle an incoming request.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, \Closure $next)
    {
        // Check if the long URL contains the current host or the app URL
        if (
            str_contains($request->long_url, $request->getHost())
            || str_contains($request->long_url, Uri::of(config('app.url'))->host())
        ) {
            return redirect()->back()
                ->with('flash_error', __('Sorry, we do not allow shortening internal links.'));
        }

        // Ensure that unique random keys can be generated by checking the remaining
        // capacity of the key generator service. If the capacity is zero, it indicates
        // that no more unique keys can be generated.
        if (app(KeyGeneratorService::class)->remainingCapacity() === 0) {
            return redirect()->back()
                ->with(
                    'flash_error',
                    __('Sorry, our service is currently under maintenance.'),
                );
        }

        return $next($request);
    }
}
