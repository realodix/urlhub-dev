<?php

namespace App\Jobs;

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
     * @return \App\Models\Url
     */
    public function handle(array $data)
    {
        return Url::create([
            'user_id'     => $data['user_id'],
            'destination' => $data['destination'],
            'title'       => $data['title'],
            'keyword'     => $data['keyword'],
            'is_custom'   => $data['is_custom'],
            'ip'          => $data['ip'],
        ]);
    }
}
