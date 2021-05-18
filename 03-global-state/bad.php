<?php declare(strict_types=1);

@mkdir(__DIR__ . '/temp'); // @ - directory may already exist
$account = __DIR__ . '/temp/bank-account.txt';
if (!file_exists($account)) {
	file_put_contents($account, '500');
}


// ---- somewhere deep in the application, written years ago ----

class PaymentGateway
{
	private static ?self $instance = null;


	public static function getInstance(): self
	{
		return self::$instance ??= new self;
	}


	public function charge(string $card, int $amount): void
	{
		$file = __DIR__ . '/temp/bank-account.txt';
		$balance = (int) file_get_contents($file) - $amount;
		file_put_contents($file, (string) $balance);
		echo "[PaymentGateway] charged \$$amount to card $card\n";
		echo "[PaymentGateway] balance is now \$$balance\n";
	}
}


class CreditCard
{
	public function __construct(
		private string $number,
	) {
	}


	public function charge(int $amount): void
	{
		// ⛔ Nothing in this class's API says a payment gateway exists.
		PaymentGateway::getInstance()->charge($this->number, $amount);
	}
}


// ---- and here is the little test I wrote on my second day ----

// The whole testing framework this course needs.
function test(string $expectation, bool $passed): void
{
	echo ($passed ? '[PASS] ' : '[FAIL] ') . $expectation . "\n";
	if (!$passed) {
		exit(1);
	}
}


function testCreditCardCharge(): void
{
	$account = __DIR__ . '/temp/bank-account.txt';
	$before = (int) file_get_contents($account);

	$card = new CreditCard('1234567890123456');
	$card->charge(100);

	// ⛔ The card offers no seam, so the only observable effect of charge()
	// is the balance. To assert anything, the money has to actually move.
	test('the card was charged $100', (int) file_get_contents($account) === $before - 100);
}


testCreditCardCharge();

echo "\nThe test passes. It also spent $100 to find that out.\n";
echo "Run it again. And again. The balance keeps dropping.\n";
