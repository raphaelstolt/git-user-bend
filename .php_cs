<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$cacheDir = getenv('TRAVIS') ? getenv('HOME') . '/.php-cs-fixer' : __DIR__;

$rules = [
    'psr0' => false,
    '@PSR2' => true,
    'ordered_imports' => ['alpha'],
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setFinder($finder)
    ->setCacheFile($cacheDir . '/.php_cs.cache');
