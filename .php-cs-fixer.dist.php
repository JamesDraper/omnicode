<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())->in(__DIR__)->append([
    __DIR__ . '/src/omnicode',
]); 

$rules = [
    '@auto' => true,
    '@auto:risky' => true,
    '@PhpCsFixer:risky' => true,
    'php_unit_test_case_static_method_calls' => ['call_type' => 'this']
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules($rules);
