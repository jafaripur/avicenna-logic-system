<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = PhpCsFixer\Finder::create()
    ->exclude([
        'vendor',
        '.git',
        '.phpunit.cache',
        '.phplint.cache'
    ])
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

$config->setRules([
        '@PSR12' => true,
        //'@PHP81Migration' => true,
        '@PHP83Migration' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => true,
        'backtick_to_shell_exec' => true,
        'braces' => [
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure' => true,
        ],
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'class_definition' => [
            'single_line' => true,
        ],
        'cast_spaces' => true,
        //'cast_spaces' => ['space' => 'none'],
        'clean_namespace' => true,
        'class_reference_name_casing' => true,
        'concat_space' => true,
        'fully_qualified_strict_types' => true,
        'empty_loop_condition' => true,
        'function_typehint_space' => true,
        'magic_constant_casing' => true,
        'linebreak_after_opening_tag' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'native_function_type_declaration_casing' => true,
        'no_binary_string' => true,
        'no_alternative_syntax' => true,
        'no_alias_language_construct_call' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'allow_unused_params' => true],
        'no_trailing_comma_in_singleline' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_trailing_comma_in_singleline_function_call' => true,
        'no_unneeded_control_parentheses' => ['statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield', 'yield_from']],
        'no_unneeded_curly_braces' => [
            'namespaces' => true,
        ],
        'no_unused_imports' => true,
        'no_unneeded_import_alias' => true,
        'ordered_imports' => true,
        'no_unset_cast' => true,
        'object_operator_without_whitespace' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_useless_nullsafe_operator' => true,
        //'no_useless_concat_operator' => true,
        'phpdoc_align' => true,
        'normalize_index_brace' => true,
        'php_unit_method_casing' => true,
        'php_unit_fqcn_annotation' => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_tag_type' => [
            'tags' => [
                'inheritDoc' => 'inline',
            ],
        ],
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'no_short_bool_cast' => true,
        'increment_style' => true,
        'lambda_not_used_import' => true,
        'integer_literal_case' => true,
        'align_multiline_comment' => true,
        'include' => true,
        'general_phpdoc_tag_rename' => [
            'replacements' => [
                'inheritDocs' => 'inheritDoc',
            ],
        ],
        'blank_line_before_statement' => ['statements' => ['break', 'case', 'continue', 'declare', 'default', 'exit', 'goto', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'yield', 'yield_from']],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'explicit_indirect_variable' => true,
        'echo_tag_syntax' => true,
        'empty_loop_body' => ['style' => 'braces'],
        'explicit_string_variable' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'no_null_property_initialization' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'operator_linebreak' => ['only_booleans' => true],
        'ordered_class_elements' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_order' => [
            'order' => [
                'param',
                'return',
                'throws',
            ],
        ],
        'phpdoc_order_by_value' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_var_without_name' => true,
        'single_line_comment_spacing' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'return_assignment' => true,
        'simple_to_complex_string_variable' => true,
        'single_class_element_per_statement' => true,
        'semicolon_after_instruction' => true,
        'single_import_per_statement' => true,
        'single_line_comment_style' => [
            'comment_types' => [
                'hash',
            ],
        ],
        'single_line_throw' => true,
        'single_quote' => true,
        'single_space_after_construct' => true,
        'space_after_semicolon' => [
            'remove_in_empty_for_expressions' => true,
        ],
        'standardize_increment' => true,
        'standardize_not_equals' => true,
        'switch_continue_to_break' => true,
        'types_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
        'no_trailing_whitespace_in_string' => true,
        'no_unreachable_default_argument_value' => true,
        'yoda_style' => true,
        'escape_implicit_backslashes' => true,
        'heredoc_to_nowdoc' => true,
        'method_argument_space' => [
            //'on_multiline' => 'ensure_fully_multiline',
            'on_multiline' => 'ignore',
        ],
        'method_chaining_indentation' => true,

    ])
    ->setRiskyAllowed(true)
    //->setParallelConfig(ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__. '/.php-cs-fixer.cache')
    ->setFinder($finder);

return $config;