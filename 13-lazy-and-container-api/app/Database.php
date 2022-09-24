<?php declare(strict_types=1);

class Database
{
	public function __construct(
		private string $dsn,
		private string $user,
	) {
		echo "[Database] connected to $this->dsn as $this->user\n";
	}


	public function ping(): void
	{
		// touches no property, so a lazy proxy stays asleep here
		echo "[Database] ping\n";
	}


	public function query(string $sql): void
	{
		// reads $this->dsn, which is what wakes a lazy proxy up
		echo "[Database] $sql on $this->dsn\n";
	}
}
