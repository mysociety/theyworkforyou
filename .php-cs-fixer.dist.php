<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'commonlib',
    ])
;
return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS' => true,
        '@PHP74Migration' => true,
        'braces_position' => ['functions_opening_brace' => 'same_line', 'classes_opening_brace' => 'same_line'],
    ])
    ->setFinder($finder)
;