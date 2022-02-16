<?php declare(strict_types=1);

class SmtpMailer implements Mailer
{
	public function __construct(
		private string $host,
		private int $port,
		private bool $secure,
	) {
		$scheme = $this->secure ? 'smtps' : 'smtp';
		echo "[SmtpMailer] using $scheme://$this->host:$this->port\n";
	}


	public function send(string $to, string $subject): void
	{
		echo "[SmtpMailer] to $to: $subject\n";
	}
}
