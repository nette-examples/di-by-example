<?php declare(strict_types=1);

class UpdateStatistics implements EventHandler
{
	private bool $verbose = false;


	public function setVerbose(bool $verbose): void
	{
		$this->verbose = $verbose;
	}


	public function handle(string $event): void
	{
		echo '[UpdateStatistics] ' . ($this->verbose ? "handling $event verbosely" : "handling $event") . "\n";
	}
}
