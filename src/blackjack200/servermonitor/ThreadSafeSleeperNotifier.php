<?php

namespace blackjack200\servermonitor;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperNotifier;

class ThreadSafeSleeperNotifier extends ThreadSafe {
	public function __construct(
		private readonly ThreadSafeArray $sharedObject,
		private readonly int             $notifierId
	) {
	}

	public static function fromNotifier(SleeperNotifier $notifier) : self {
		return new self((fn() => $this->sharedObject)->call($notifier), (fn() => $this->notifierId)->call($notifier));
	}

	final public function wakeupSleeper() : void {
		$shared = $this->sharedObject;
		$sleeperId = $this->notifierId;
		$shared->synchronized(function() use ($shared, $sleeperId) : void {
			if (!isset($shared[$sleeperId])) {
				$shared[$sleeperId] = $sleeperId;
				$shared->notify();
			}
		});
	}

}