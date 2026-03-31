<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/example',
    ])
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_line_empty_body' => false,
    ])
    ->setFinder($finder);
