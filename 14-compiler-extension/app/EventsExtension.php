<?php declare(strict_types=1);

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;


/** The shape of our config section, written as a class so it carries types. */
class EventsConfig
{
	public string $tag = 'subscriber';
	public bool $enabled = true;
}


/**
 * Adds an "events" section to the configuration, registers a dispatcher,
 * and hands it every service tagged as a subscriber.
 */
class EventsExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		// Derives the schema from the class above, defaults included
		return Expect::from(new EventsConfig);
	}


	public function loadConfiguration(): void
	{
		if (!$this->getEventsConfig()->enabled) {
			return;
		}

		// Register the dispatcher; its handlers are filled in later
		$this->getContainerBuilder()
			->addDefinition($this->prefix('dispatcher'))
			->setFactory(EventDispatcher::class);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		if (!$builder->hasDefinition($this->prefix('dispatcher'))) {
			return;
		}

		// Every other extension has had its say by now, so the tags are complete
		$handlers = array_keys($builder->findByTag($this->getEventsConfig()->tag));
		$refs = array_map(fn(string $name) => '@' . $name, $handlers);

		$definition = $builder->getDefinition($this->prefix('dispatcher'));
		if ($definition instanceof ServiceDefinition) {
			$definition->setArgument('handlers', $refs);
			echo '[EventsExtension] wired ' . count($refs) . " handlers at compile time\n";
		}
	}


	private function getEventsConfig(): EventsConfig
	{
		return $this->config instanceof EventsConfig
			? $this->config
			: new EventsConfig;
	}
}
