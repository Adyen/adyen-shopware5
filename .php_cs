<?php

$finder = \PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor'])
    ->name('*.php')
    ->ignoreDotFiles(true);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);