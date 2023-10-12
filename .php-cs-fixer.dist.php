<?php

$header = <<<'EOF'
This file is part of Ymir WordPress plugin.

(c) Carl Alexander <support@ymirapp.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'align_multiline_comment' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        // one should use PHPUnit methods to set up expected exception instead of annotations
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedException', 'expectedExceptionMessage', 'expectedExceptionMessageRegExp']],
        'header_comment' => ['header' => $header],
        'heredoc_to_nowdoc' => true,
        'list_syntax' => ['syntax' => 'long'],
        'method_chaining_indentation' => false,
        'modernize_strpos' => true,
        'native_function_invocation' => false,
        'native_constant_invocation' => false,
        'no_extra_blank_lines' => ['tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block']],
        'no_null_property_initialization' => true,
        'echo_tag_syntax' => ['format' => 'long'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => false],
        'no_unneeded_curly_braces' => true,
        'no_unneeded_final_method' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_public_abstract',
                'method_protected',
                'method_protected_abstract',
                'method_private',
            ],
            'sort_algorithm' => 'alpha'
        ],
        'ordered_imports' => true,
        'php_unit_construct' => true,
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_test_class_requires_covers' => true,
        'php_unit_dedicate_assert' => false,
        'phpdoc_order' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'semicolon_after_instruction' => true,
        'single_line_comment_style' => true,
        'yoda_style' => true,
    ])
    ->setFinder($finder)
;

return $config;
