<?php

/**
 * This file is part of the Nette Tester.
 * Copyright (c) 2009 David Grudl (https://davidgrudl.com)
 */

namespace Tester\Runner;


/**
 * Test represents one result.
 */
class Test
{
	const
		NO_RESULT = 0, # TODO: lepsi jmeno
		PASSED = 1,
		SKIPPED = 2,
		FAILED = 3;

	/** @var string|NULL */
	public $title;

	/** @var string */
	public $message;

	/** @var string|NULL */
	public $stdout;

	/** @var string|NULL */
	public $stderr;

	/** @var string */
	private $file;

	/** @var int */
	private $result = self::NO_RESULT;

	/** @var string[][] */
	private $args = [];


	/**
	 * @param  string
	 * @param  string
	 */
	public function __construct($file, $title = NULL)
	{
		$this->file = $file;
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}


	/**
	 * @return string[][]
	 */
	public function getArguments()
	{
		return $this->args;
	}


	/**
	 * @return array
	 */
	public function getJobArguments()  # TODO: tohle sem asi nepatri
	{
		$args = [];
		foreach ($this->args as $name => $value) {
			foreach ($value as $v) {
				$args[] = is_int($name)
					? \Tester\Helpers::escapeArg($v)
					: \Tester\Helpers::escapeArg("--$name=$v");
			}
		}
		return $args;
	}


	/**
	 * @return int
	 */
	public function getResult()
	{
		return $this->result;
	}


	/**
	 * @return bool
	 */
	public function hasResult()
	{
		return $this->result !== self::NO_RESULT;
	}


	/**
	 * @param  array $args
	 * @return static
	 */
	public function withArguments(array $args)
	{
		$me = clone $this;
		foreach ($args as $name => $value) {
			foreach ((array) $value as $v) {
				$me->args[$name][] = $v;
			}
		}
		return $me;
	}


	/**
	 * @param  int
	 * @param  string
	 * @return static
	 */
	public function withResult($result, $message)
	{
		# TODO: Vyjimku, pokud je test failed?
		$me = clone $this;
		$me->result = $result;
		$me->message = $message;
		return $me;
	}

}
