<?php declare(strict_types=1);

// A test double: implements the interface and remembers, instead of sending.
class FakeMailer implements Mailer
{
	/** @var string[] */
	public array $sent = [];


	public function send(string $to, string $subject): void
	{
		$this->sent[] = $to;
	}
}
