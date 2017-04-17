<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;

use Tester;
use Tester\Dumper;
use Tester\Helpers;


/**
 * Default test behavior.
 */
class TestHandler
{
	const HTTP_OK = 200;

	/** @var Runner */
	private $runner;


	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
	}


	/**
	 * @param  string
	 * @return Test[]
	 */
	public function initiate($file)
	{
		list($annotations, $title) = $this->getAnnotations($file);
		$php = clone $this->runner->getInterpreter();

		$tests = [new Test($file, $title)];
		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^initiate(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}

			foreach ((array) $annotations[$m[1]] as $value) {
				/** @var Test[] $tmp */
				$tmp = [];
				foreach ($tests as $test) {
					$res = $this->$method($test, $value, $php);
					if ($res === NULL) {
						$tmp[] = $test;
					} else {
						foreach (is_array($res) ? $res : [$res] as $testVariant) {
							$tmp[] = $testVariant;
						}
					}
				}
				$tests = $tmp;
			}
		}

		foreach ($tests as $test) {
			if (!$test->hasResult()) {
				$this->runner->addJob(new Job($test, $php, $this->runner->getEnvironmentVariables()));
			}
		}

		return $tests;
	}


	/**
	 * @return void
	 */
	public function assess(Job $job)
	{
		$test = $job->getTest();
		list($annotations, $testName) = $this->getAnnotations($test->getFile());
//$testName .= /*$job->getArguments()*/ [] # TODO
//? ' [' . implode(' ', preg_replace(['#["\'-]*(.+?)["\']?$#A', '#(.{30}).+#A'], ['$1', '$1...'], /*$job->getArguments()*/[])) . ']' # TODO
//: '';
		$annotations += [
			'exitcode' => Job::CODE_OK,
			'httpcode' => self::HTTP_OK,
		];

		foreach (get_class_methods($this) as $method) {
			if (!preg_match('#^assess(.+)#', strtolower($method), $m) || !isset($annotations[$m[1]])) {
				continue;
			}
			foreach ((array) $annotations[$m[1]] as $arg) {
				/** @var Test|NULL $res */
				if ($res = $this->$method($job, $arg)) {  # TODO: Je potreba predavat Job? Ted pouze pro exitCode.
					$this->runner->writeResult($res);
					return;
				}
			}
		}
		$this->runner->writeResult($test->withResult(Test::PASSED, $test->message));
	}


	private function initiateSkip(Test $test, $message)
	{
		return $test->withResult(Test::SKIPPED, $message);
	}


	private function initiatePhpVersion(Test $test, $version, PhpInterpreter $interpreter)
	{
		if (preg_match('#^(<=|<|==|=|!=|<>|>=|>)?\s*(.+)#', $version, $matches)
			&& version_compare($matches[2], $interpreter->getVersion(), $matches[1] ?: '>='))
		{
			return $test->withResult(Test::SKIPPED, "Requires PHP $version.");
		}
	}


	private function initiatePhpIni(Test $test, $pair, PhpInterpreter $interpreter)
	{
		list($name, $value) = explode('=', $pair, 2) + [1 => NULL];
		$interpreter->addPhpIniOption($name, $value);
	}


	private function initiateDataProvider(Test $test, $provider, PhpInterpreter $interpreter)
	{
		try {
			list($dataFile, $query, $optional) = Tester\DataProvider::parseAnnotation($provider, $test->getFile());
			$data = Tester\DataProvider::load($dataFile, $query);
		} catch (\Exception $e) {
			return $test->withResult(empty($optional) ? Test::FAILED : Test::SKIPPED, $e->getMessage());
		}

		$tests = [];
		foreach (array_keys($data) as $item) {
			$tests[] = $test->withArguments(['dataprovider' => "$item|$dataFile"]);
		}
		return $tests;
	}


	private function initiateMultiple(Test $test, $count)
	{
		$tests = [];
		foreach (range(0, (int) $count - 1) as $i) {
			$tests[] = $test->withArguments(['multiple' => $i]);
		}
		return $tests;
	}


	private function initiateTestCase(Test $test, $foo, PhpInterpreter $interpreter)
	{
		$job = new Job($test->withArguments(['method' => Tester\TestCase::LIST_METHODS]), $interpreter);
		$job->run();

		if (in_array($job->getExitCode(), [Job::CODE_ERROR, Job::CODE_FAIL, Job::CODE_SKIP], TRUE)) {
			return $test->withResult($job->getExitCode() === Job::CODE_SKIP ? Test::SKIPPED : Test::FAILED, $job->getTest()->stdout);
		}

		if (!preg_match('#\[([^[]*)]#', strrchr($job->getTest()->stdout, '['), $m)) {
			return $test->withResult(Test::FAILED, "Cannot list TestCase methods in file '{$test->getFile()}'. Do you call TestCase::run() in it?");
		} elseif (!strlen($m[1])) {
			return $test->withResult(Test::SKIPPED, "TestCase in file '{$test->getFile()}' does not contain test methods.");
		}

		$tests = [];
		foreach (explode(',', $m[1]) as $method) {
			$tests[] = $test->withArguments(['method' => $method]);
		}
		return $tests;
	}


	private function assessExitCode(Job $job, $code)
	{
		$code = (int) $code;
		if ($job->getExitCode() === Job::CODE_SKIP) {
			$message = preg_match('#.*Skipped:\n(.*?)\z#s', $output = $job->getTest()->stdout, $m)
				? $m[1]
				: $output;
			return $job->getTest()->withResult(Test::SKIPPED, trim($message));

		} elseif ($job->getExitCode() !== $code) {
			$message = $job->getExitCode() !== Job::CODE_FAIL ? "Exited with error code {$job->getExitCode()} (expected $code)" : '';
			return $job->getTest()->withResult(Test::FAILED, trim($message . "\n" . $job->getTest()->stdout));  # TODO
		}
	}


	private function assessHttpCode(Job $job, $code)
	{
		if (!$this->runner->getInterpreter()->isCgi()) {
			return;
		}
		$headers = $job->getHeaders();
		$actual = isset($headers['Status']) ? (int) $headers['Status'] : self::HTTP_OK;
		$code = (int) $code;
		if ($code && $code !== $actual) {
			return $job->getTest()->withResult(Test::FAILED, "Exited with HTTP code $actual (expected $code)");
		}
	}


	private function assessOutputMatchFile(Job $job, $file)
	{
		$file = dirname($job->getTest()->getFile()) . DIRECTORY_SEPARATOR . $file;
		if (!is_file($file)) {
			return $job->getTest()->withResult(Test::FAILED, "Missing matching file '$file'.");
		}
		return $this->assessOutputMatch($job, file_get_contents($file)); # TODO
	}


	private function assessOutputMatch(Job $job, $content)
	{
		$test = $job->getTest();
		$actual = $test->stdout;
		if (!Tester\Assert::isMatching($content, $actual)) {
			list($content, $actual) = Tester\Assert::expandMatchingPatterns($content, $actual);
			Dumper::saveOutput($test->getFile(), $actual, '.actual');
			Dumper::saveOutput($test->getFile(), $content, '.expected');
			return $test->withResult(Test::FAILED, 'Failed: output should match ' . Dumper::toLine($content));
		}
	}


	private function getAnnotations($file)
	{
		$annotations = Helpers::parseDocComment(file_get_contents($file));
		$testName = (isset($annotations[0]) ? preg_replace('#^TEST:\s*#i', '', $annotations[0]) . ' | ' : '')
			. implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));
		return [$annotations, $testName];
	}

}
