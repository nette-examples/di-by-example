<?php declare(strict_types=1);

interface EventHandler
{
	function handle(string $event): void;
}
