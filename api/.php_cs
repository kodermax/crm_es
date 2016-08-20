<?php
$header = <<<EOF
This file is part of the Synergy package.

Copyright (c) 2015-2016 Synergy.

@author Maksim Karpychev <mkarpychev@synergy.ru>
EOF;


$finder = Symfony\Component\Finder\Finder::create()
	->notPath('bootstrap/cache')
	->notPath('storage')
	->notPath('vendor')
    ->in('app')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$fixers = [

];

return Symfony\CS\Config\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@Symfony' => true,
        'header_comment' => array('header' => $header),
        'long_array_syntax' => true,
        'ordered_use' => true,
        'php_unit_construct' => true,
        'php_unit_strict' => true,
        'strict' => true,
        'short_array_syntax' => true,
        'strict_param' => true,
    ))
    ->finder($finder)
    ->setUsingCache(true);