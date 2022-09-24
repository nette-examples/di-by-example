<?php declare(strict_types=1);

class EventDispatcher
{
	/** @param  EventHandler[]  $handlers */
	public function __construct(
		private array $handlers,
		private Logger $logger,
	) {
	}


	public function dispatch(string $event): void
	{
		$this->logger->log(count($this->handlers) . " handlers for $event");
		foreach ($this->handlers as $handler) {
			$handler->handle($event);
		}
	}
}
