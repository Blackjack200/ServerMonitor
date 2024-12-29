<?php


namespace blackjack200\servermonitor;


use LogLevel;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\log\ThreadSafeLoggerAttachment;
use pocketmine\utils\TextFormat;

class ErrorLoggerAttachment extends ThreadSafeLoggerAttachment {

	protected ThreadSafeSleeperNotifier $notifier;

	public function __construct(private ThreadSafeArray $buffer, SleeperHandlerEntry $entry) {
		$this->notifier = ThreadSafeSleeperNotifier::fromNotifier($entry->createNotifier());
	}

	public function log($level, $message) : void {
		$this->buffer->synchronized(function() use ($message, $level) {
			if (in_array($level, [LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ERROR, LogLevel::WARNING], true)) {
				$this->buffer[] = TextFormat::clean($message);
			}
		});
		$this->notifier->wakeupSleeper();
	}

	public function getBuffer() : ThreadSafeArray {
		return $this->buffer;
	}
}