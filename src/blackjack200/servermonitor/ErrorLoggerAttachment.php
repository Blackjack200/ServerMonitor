<?php


namespace blackjack200\servermonitor;


use LogLevel;
use pmmp\thread\ThreadSafeArray;
use pocketmine\thread\log\ThreadSafeLoggerAttachment;
use pocketmine\utils\TextFormat;

class ErrorLoggerAttachment extends ThreadSafeLoggerAttachment {
	private ThreadSafeArray $buffer;

	public function __construct() {
		$this->buffer = new ThreadSafeArray();
	}

	public function log($level, $message) : void {
		$this->synchronized(function() use ($message, $level) {
			if (in_array($level, [LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ERROR, LogLevel::WARNING], true)) {
				$this->buffer[] = TextFormat::clean($message);
			}
		});
	}

	public function getBuffer() : ThreadSafeArray {
		return $this->buffer;
	}
}