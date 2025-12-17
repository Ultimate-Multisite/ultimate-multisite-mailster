<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/inc',
			__DIR__ . '/views',
		]
	)
	->withSkipPath(__DIR__ . '/vendor',)
	->withImportNames(false)
	->withPhpSets(php74: true)
	->withCodeQualityLevel(15)
	->withCodingStyleLevel(5);
