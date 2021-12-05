<?php declare(strict_types=1);

class ShipManager
{
	/** @param  Shipper[]  $shippers */
	public function __construct(
		private array $shippers,
	) {
	}


	public function shipAll(string $order): void
	{
		foreach ($this->shippers as $shipper) {
			$shipper->ship($order);
		}
	}
}
