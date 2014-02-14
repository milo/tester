<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../Tester/Runner/PhpExecutable.php';

if (PHP_VERSION_ID < 50400) {
	Tester\Environment::skip('Requires constant PHP_BINARY available since PHP 5.4.0');
}

$php = new Tester\Runner\PhpExecutable(PHP_BINARY);


$stdout = "OUT1\nOUT2\n";
$stderr = "ERR1\nERR2\nERR3\n";

$job = new Tester\Runner\Job(__DIR__ . '/job/outputs.php', $php);
$job->run(TRUE);

Assert::same( $stdout, $job->getOutput() );
Assert::same( $stderr, $job->getErrorOutput() );


$job = new Tester\Runner\Job(__DIR__ . '/job/outputs.php', $php);
$job->run(FALSE);
while ($job->isRunning()) {
	usleep($job::RUN_USLEEP);
}

Assert::same( $stdout, $job->getOutput() );
Assert::same( $stderr, $job->getErrorOutput() );
