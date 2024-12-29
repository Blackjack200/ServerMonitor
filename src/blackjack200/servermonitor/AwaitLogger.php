<?php
declare(strict_types=1);

namespace blackjack200\servermonitor;

use InvalidArgumentException;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Timezone;
use RuntimeException;

class AwaitLogger {
	protected SleeperNotifier $notifier;
	/** @var resource */
	private $handle;
	private \Generator $context;

	public function __construct(private string $file) {
		$handle = fopen($this->file, 'ab');
		if ($handle === false) {
			throw new InvalidArgumentException("Failed to open log file: {$this->file}");
		}
		$this->handle = $handle;

		if (!stream_set_blocking($this->handle, false)) {
			throw new RuntimeException("Failed to set non-blocking mode for log file: {$this->file}");
		}

		$this->context = $this->flushGenerator();

		$this->notifier = Server::getInstance()->getTickSleeper()->addNotifier(function() : void {
			$this->context->next();
		})->createNotifier();
	}

	protected function flushGenerator() : \Generator {
		$timezone = new \DateTimeZone(Timezone::get());
		while (true) {
			$message = yield;
			if ($message !== null) {
				$time = new \DateTime('now', $timezone);
				$message = $time->format("Y-m-d[H:i:s.v] ") . $message;
				while ($message !== '') {
					$written = fwrite($this->handle, $message . PHP_EOL);
					if ($written === false) {
						throw new RuntimeException("Failed to write to log file: {$this->file}");
					}
					$message = substr($message, $written);
				}
			}
		}
	}

	public function __destruct() {
		fclose($this->handle);
	}

	public function write(string $message) : void {
		$this->context->send($message);
		$this->notifier->wakeupSleeper();
	}
}