<?php declare(strict_types=1);

interface Mailer
{
	function send(string $to, string $subject): void;
}
