<?php declare(strict_types=1);

class ConsoleLogger implements Logger
{
	public function log(string $message): void
	{
		echo "[log] $message\n";
	}
}
