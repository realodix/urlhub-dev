<?php

use Realodix\Relax\Config;
use Realodix\Relax\Finder;

$localRules = [
    // Base
    'single_import_per_statement' => false,

    // Relax
    'no_empty_comment'  => false,
];

return Config::create('relax')
    ->setRules($localRules)
    ->setFinder(Finder::laravel()->in(__DIR__))
    ->setCacheFile(__DIR__.'/.tmp/.php-cs-fixer.cache');
