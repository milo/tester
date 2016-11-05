<?php

use Tester\Assert;
use Tester\Runner\Job;
use Tester\Runner\Test;

require __DIR__ . '/../bootstrap.php';


test(function () {
	$test = (new Test($file = 'Job.test.phptx'))->withArguments($args = ['one', 'two']);
	$job = new Job($test, createInterpreter());
	$job->run($job::RUN_COLLECT_ERRORS);

	Assert::false($job->isRunning());
//	Assert::same($file, $job->getTest()->getFile());  # TODO: tohle do testu Test
//	Assert::same($args, $job->getTest()->getArguments());
	Assert::same(231, $job->getExitCode());

	if (defined('PHPDBG_VERSION') && PHP_VERSION_ID === 70000) { // bug #71056
		Assert::same('Args: one, twoError1-outputError2', $job->getTest()->stdout);
		Assert::same('', $job->getTest()->stderr);
	} else {
		Assert::same('Args: one, two-output', $job->getTest()->stdout);
		Assert::same('Error1Error2', $job->getTest()->stderr);
	}

	if (PHP_SAPI !== 'cli') {
		Assert::contains('Nette Tester', $job->getHeaders());
	}
});
