<?php declare(strict_types=1);

interface Shipper
{
	function ship(string $order): void;
}
