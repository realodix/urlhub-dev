<?php

namespace Database\Factories;

use App\Models\Url;
use App\Models\User;
use App\Services\UrlKeyService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Url>
 */
class UrlFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Url>
     */
    protected $model = Url::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'     => User::factory(),
            'destination' => 'https://github.com/realodix/urlhub',
            'title'       => 'No Title',
            'keyword'     => app(UrlKeyService::class)->randomString(),
            'is_custom'   => false,
            'ip'          => fake()->ipv4(),
        ];
    }
}
