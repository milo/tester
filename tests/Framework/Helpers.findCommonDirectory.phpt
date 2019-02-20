<?php

declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/../bootstrap.php';

$ds = function (string $path): string {
	return str_replace('/', DIRECTORY_SEPARATOR, $path);
};

Assert::same('', Helpers::findCommonDirectory([]));

Assert::match($ds('/foo/bar'), Helpers::findCommonDirectory([
	'/foo/bar/aaa',
	'/foo/bar/a',
]));

Assert::same($ds('C:/www'), Helpers::findCommonDirectory([
	'C:\www\foo',
	'C:\www\bar',
]));

Assert::same('', Helpers::findCommonDirectory([
	'C:\www',
	'D:\www',
]));

Assert::same($ds('/foo'), Helpers::findCommonDirectory([
	'/foo/bar',
]));

Assert::same($ds('/foo/bar'), Helpers::findCommonDirectory([
	'/foo/bar/',
]));

Assert::same('', Helpers::findCommonDirectory([
	'',
]));

Assert::same($ds('/'), Helpers::findCommonDirectory([
	'/',
]));

Assert::same('', Helpers::findCommonDirectory([
	'/',
	'',
]));

Assert::same($ds('/'), Helpers::findCommonDirectory([
	'/foo',
	'/',
]));

Assert::same($ds('/'), Helpers::findCommonDirectory([
	'/foo/bar/a',
	'/foo/bar',
	'/var',
]));


Assert::same('', Helpers::findCommonDirectory([
	'C:',
]));

Assert::same('C:', Helpers::findCommonDirectory([
	'C:\\',
]));

Assert::same('C:', Helpers::findCommonDirectory([
	'C:\www',
]));

Assert::same($ds('C:/www'), Helpers::findCommonDirectory([
	'C:\www\\',
]));
