<?php

/**
 * @outputMatch Test::setUp,Test::testMe,Test::tearDown
 */

require __DIR__ . '/../bootstrap.php';

Tester\Environment::$useColors = FALSE;


class Test extends Tester\TestCase
{
	protected function setUp()
	{
		echo __METHOD__ . ',';
	}

	/** @dataProvider data */
	public function testMe($arg)
	{
		echo __METHOD__ . ',';
		@trigger_error('MUTED', E_USER_WARNING);
	}

	protected function tearDown()
	{
		echo __METHOD__;
		parent::tearDown();
	}

	protected function data()
	{
		return [['arg']];
	}
}

(new Test)->run();
