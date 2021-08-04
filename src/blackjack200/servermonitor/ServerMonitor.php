<?php


namespace blackjack200\servermonitor;


use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use Webmozart\PathUtil\Path;

class ServerMonitor extends PluginBase implements Listener {
	private static self $instance;
	public LogThread $TPSLogger;
	public LogThread $commandLogger;
	public LogThread $errorLogger;

	public static function getInstance() : self {
		return self::$instance;
	}

	protected function onEnable() : void {
		self::$instance = $this;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$path = Path::join($this->getServer()->getDataPath(), 'monitor');
		@mkdir($path);
		$this->commandLogger = new LogThread(Path::join($path, 'command.log'));
		$this->commandLogger->start();
		$this->TPSLogger = new LogThread(Path::join($path, 'TPS.log'));
		$this->TPSLogger->start();
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function () : void {
			$TPS = Server::getInstance()->getTicksPerSecond();
			if ($TPS !== 20.0) {
				ServerMonitor::getInstance()->TPSLogger->write((string) $TPS);
			}
		}), 20);
		$this->errorLogger = new LogThread(Path::join($path, 'error.log'));
		$this->errorLogger->start();
		Server::getInstance()->getLogger()->addAttachment(new ServerErrorMonitor());
	}

	protected function onDisable() : void {
		$this->commandLogger->shutdown();
		$this->TPSLogger->shutdown();
		$this->errorLogger->shutdown();
	}

	public function onCommandEvent(CommandEvent $event) : void {
		$this->commandLogger->write("SENDER={$event->getSender()->getName()} COMMAND={$event->getCommand()}");
	}
}