<?php declare(strict_types=1);

class Database
{
	public function __construct(
		private string $dsn,
		private string $user,
	) {
		echo "[Database] connected to $this->dsn as $this->user\n";
	}


	public function query(string $sql): void
	{
		echo "[Database] $sql\n";
	}
}
