<?php


namespace blackjack200\servermonitor;


use LogLevel;
use pocketmine\utils\TextFormat;
use ThreadedLoggerAttachment;

class ServerErrorMonitor extends ThreadedLoggerAttachment {
	public function log($level, $message) : void {
		if (in_array($level, [LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ERROR, LogLevel::WARNING], true)) {
			ServerMonitor::getInstance()->errorLogger->write(TextFormat::clean($message));
		}
	}
}