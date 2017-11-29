<?php
namespace Ipol\DPD;

class Error
{
	/** @var int|string */
	protected $code;

	/** @var string */
	protected $message;

	/**
	 * Creates a new Error.
	 * @param string $message Message of the error.
	 * @param int|string $code Code of the error.
	 */
	public function __construct($message, $code = 0)
	{
		$this->message = $message;
		$this->code = $code;
	}

	/**
	 * Returns the code of the error.
	 * @return int|string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns the message of the error.
	 * @return string
	 */
	public function getMessage()
	{
		return $this->message;
	}

	public function __toString()
	{
		return $this->getMessage();
	}
}
