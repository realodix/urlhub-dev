<?php

namespace App\Services\Visitor;

use App\Models\Visit;

class CreateVisitorData
{
    /**
     * Create the visitor data.
     *
     * @return void
     */
    public function execute(array $data)
    {
        $logBotVisit = config('urlhub.track_bot_visits');
        if ($logBotVisit === false && \Browser::isBot() === true) {
            return;
        }

        Visit::create([
            'url_id'          => $data['url_id'],
            'visitor_id'      => $data['visitor_id'],
            'is_first_click'  => $data['is_first_click'],
            'referer'         => $data['referer'],
            'ip'              => $data['ip'],
            'browser'         => $data['browser'],
            'browser_version' => $data['browser_version'],
            'device'          => $data['device'],
            'os'              => $data['os'],
            'os_version'      => $data['os_version'],
        ]);
    }
}