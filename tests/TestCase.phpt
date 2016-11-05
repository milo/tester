<?php

require __DIR__ . '/bootstrap.php';

use Tester\Assert;
use Tester\TestCase;


class MyTest extends TestCase
{
	public function testMe()
	{
		Assert::null(NULL);
	}

	public function testHim()
	{
		Assert::null(NULL);
	}
}

(new MyTest)->run();
