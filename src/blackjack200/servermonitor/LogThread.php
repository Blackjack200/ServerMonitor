<?php


namespace blackjack200\servermonitor;


use pmmp\thread\ThreadSafeArray;
use pocketmine\thread\Thread;
use RuntimeException;

class LogThread extends Thread {
	private string $logFile;
	private ThreadSafeArray $buffer;
	private bool $timestamp;

	public function __construct(string $file, bool $timestamp = true) {
		$this->logFile = $file;
		$this->timestamp = $timestamp;
		$this->buffer = new ThreadSafeArray();
		touch($this->logFile);
	}

	public function write(string $buffer) : void {
		$this->synchronized(function() use ($buffer) : void {
			$this->buffer[] = $buffer;
			$this->notify();
		});
	}

	public function onRun() : void {
		$logResource = fopen($this->logFile, 'ab');
		if (!is_resource($logResource)) {
			throw new RuntimeException('Cannot open log file');
		}
		while (!$this->isKilled) {
			$this->synchronized(function() use ($logResource) {
				$this->writeStream($logResource);
			});
			$this->synchronized(function() {
				if (!$this->isKilled) {
					$this->wait();
				}
			});
		}
		$this->synchronized(function() use ($logResource) {
			$this->writeStream($logResource);
		});
		fclose($logResource);
	}

	/**
	 * @param resource $stream
	 */
	protected function writeStream($stream) : void {
		while ($this->buffer->count() > 0) {
			/** @var string $line */
			$line = $this->buffer->pop();
			if ($this->timestamp) {
				$line = sprintf("[%s]: %s", date('Y-n-j_H.i.s'), $line);
			}
			fwrite($stream, $line);
			fwrite($stream, "\n");
		}
	}
}