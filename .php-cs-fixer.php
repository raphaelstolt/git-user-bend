<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in([__DIR__, __DIR__ . DIRECTORY_SEPARATOR . 'tests']);

$rules = [
    'psr_autoloading' => false,
    '@PSR2' => true,
    'phpdoc_order' => true,
    'ordered_imports' => true
];

$cacheDir = \getenv('HOME') ? \getenv('HOME') : __DIR__;

$config = new Config();

return $config->setRules($rules)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder($finder)
    ->setCacheFile($cacheDir . '/.php-cs-fixer.cache');
