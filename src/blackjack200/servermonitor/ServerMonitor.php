<?php
declare(strict_types=1);

namespace blackjack200\servermonitor;

use pmmp\thread\ThreadSafeArray;
use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use Symfony\Component\Filesystem\Path;

class ServerMonitor extends PluginBase implements Listener {
	private static self $instance;
	public AwaitLogger $TPSLogger;
	public AwaitLogger $commandLogger;
	public AwaitLogger $errorLogger;

	public static function getInstance() : self {
		return self::$instance;
	}

	protected function onEnable() : void {
		self::$instance = $this;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$path = Path::join($this->getServer()->getDataPath(), 'monitor');
		@mkdir($path);

		$this->commandLogger = new AwaitLogger(Path::join($path, 'command.log'));
		$this->TPSLogger = new AwaitLogger(Path::join($path, 'TPS.log'));
		$this->errorLogger = new AwaitLogger(Path::join($path, 'error.log'));

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() : void {
			$TPS = Server::getInstance()->getTicksPerSecond();
			if ($TPS !== 20.0) {
				$player = count(Server::getInstance()->getOnlinePlayers());
				ServerMonitor::getInstance()->TPSLogger->write("TPS=$TPS PLAYER=$player");
			}
		}), 20);


		$buf = new ThreadSafeArray();
		$notifier = $this->getServer()->getTickSleeper()->addNotifier(function() use ($buf) {
			$buf->synchronized(function() use ($buf) : void {
				while (($line = $buf->shift()) !== null) {
					$this->errorLogger->write($line);
				}
			});
		});
		Server::getInstance()->getLogger()->addAttachment(new ErrorLoggerAttachment($buf, $notifier));
	}

	protected function onDisable() : void { }

	public function onCommandEvent(CommandEvent $event) : void {
		$this->commandLogger->write("SENDER={$event->getSender()->getName()} COMMAND={$event->getCommand()}");
	}
}