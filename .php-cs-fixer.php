<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__);
$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PhpCsFixer' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'phpdoc_to_comment' => ['ignored_tags' => ['todo']],
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder);
