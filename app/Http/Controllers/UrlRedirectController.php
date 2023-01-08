<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Services\UrlRedirection;
use App\Services\VisitorService;
use Illuminate\Support\Facades\DB;

class UrlRedirectController extends Controller
{
    public function __construct(
        public UrlRedirection $urlRedirection,
        public VisitorService $visitorService,
    ) {
    }

    /**
     * Redirect the client to the intended long URL (no checks are performed)
     * and executes the create visitor data task.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(string $key)
    {
        return DB::transaction(function () use ($key) {
            $url = Url::whereKeyword($key)->firstOrFail();

            $this->visitorService->create($url);

            return $this->urlRedirection->execute($url);
        });
    }
}
