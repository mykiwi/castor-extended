<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreVCSIgnored(true)
    ->ignoreDotFiles(false)
    ->in(__DIR__)
    ->exclude('examples')
    ->append([__FILE__])
;

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP84Migration' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_add_missing_param_annotation' => false,
        'concat_space' => ['spacing' => 'one'],
        'single_import_per_statement' => ['group_to_single_imports' => false],
    ])
    ->setFinder($finder)
;
