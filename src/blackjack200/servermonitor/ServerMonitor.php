<?php


namespace blackjack200\servermonitor;


use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use Symfony\Component\Filesystem\Path;

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
				$player = count(Server::getInstance()->getOnlinePlayers());
				$worlds = Server::getInstance()->getWorldManager()->getWorlds();
				$buf = '';
				foreach ($worlds as $world) {
					$buf .= sprintf('W_%s=%s ', $world->getFolderName(), $world->getTickRateTime());
				}
				$buf = substr($buf, 0, -1);
				ServerMonitor::getInstance()->TPSLogger->write("TPS=$TPS PLAYER=$player $buf");
			}
		}), 20);
		$this->errorLogger = new LogThread(Path::join($path, 'error.log'), false);
		$this->errorLogger->start();
		$attachment = new ErrorLoggerAttachment();
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function () use ($attachment) : void {
			$buf = $attachment->getBuffer();
			while ($buf->count() > 0) {
				ServerMonitor::getInstance()->errorLogger->write($buf->pop());
			}
		}), 40);
		Server::getInstance()->getLogger()->addAttachment($attachment);
	}

	protected function onDisable() : void {
		$this->commandLogger->quit();
		$this->TPSLogger->quit();
		$this->errorLogger->quit();
	}

	public function onCommandEvent(CommandEvent $event) : void {
		$this->commandLogger->write("SENDER={$event->getSender()->getName()} COMMAND={$event->getCommand()}");
	}
}