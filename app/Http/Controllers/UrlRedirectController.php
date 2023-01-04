<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Jobs\UrlRedirection;
use App\Models\Url;
use App\Models\Visit;
use App\Services\Visitor\CreateVisitorData;
use Illuminate\Support\Facades\DB;

class UrlRedirectController extends Controller
{
    public function __construct(
        public Visit $visit,
        public CreateVisitorData $createVisitorData,
    ) {
        //
    }

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

            $data = [
                'url_id'          => $url->id,
                'visitor_id'      => $this->visit->visitorId(),
                'is_first_click'  => $this->visit->isFirstClick($url),
                'referer'         => request()->header('referer'),
                'ip'              => Helper::anonymizeIp(request()->ip()),
                'browser'         => \Browser::browserFamily(),
                'browser_version' => \Browser::browserVersion(),
                'device'          => \Browser::deviceType(),
                'os'              => \Browser::platformFamily(),
                'os_version'      => \Browser::platformVersion(),
            ];

            $this->createVisitorData->execute($data);

            return $urlRedirection->handle($url);
        });
    }
}
