<?php declare(strict_types=1);

class DhlShipper implements Shipper
{
	public function ship(string $order): void
	{
		echo "[DHL] shipping $order\n";
	}
}
