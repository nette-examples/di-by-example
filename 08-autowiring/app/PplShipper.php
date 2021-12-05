<?php declare(strict_types=1);

class PplShipper implements Shipper
{
	public function ship(string $order): void
	{
		echo "[PPL] shipping $order\n";
	}
}
