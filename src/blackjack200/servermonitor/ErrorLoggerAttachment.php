<?php


namespace blackjack200\servermonitor;


use LogLevel;
use pocketmine\utils\TextFormat;
use Threaded;
use ThreadedLoggerAttachment;

class ErrorLoggerAttachment extends ThreadedLoggerAttachment {
	private Threaded $buffer;

	public function __construct() {
		$this->buffer = new Threaded();
	}

	public function log($level, $message) : void {
		if (in_array($level, [LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ERROR, LogLevel::WARNING], true)) {
			$this->buffer[] = TextFormat::clean($message);
		}
	}

	public function getBuffer() : Threaded {
		return $this->buffer;
	}
}