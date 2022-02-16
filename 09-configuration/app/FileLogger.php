<?php declare(strict_types=1);

class FileLogger implements Logger
{
	private string $prefix = '';


	public function __construct(
		private string $file,
	) {
		echo '[FileLogger] writing to ' . basename($this->file) . "\n";
	}


	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
		echo "[FileLogger] prefix set to $prefix\n";
	}


	public function log(string $message): void
	{
		file_put_contents($this->file, "$this->prefix $message\n", FILE_APPEND);
		echo "[FileLogger] $this->prefix $message\n";
	}
}
