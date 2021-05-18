<?php declare(strict_types=1);

@mkdir(__DIR__ . '/temp'); // @ - directory may already exist
$account = __DIR__ . '/temp/bank-account.txt';
if (!file_exists($account)) {
	file_put_contents($account, '500');
}


interface PaymentGateway
{
	public function charge(string $card, int $amount): void;
}


class RealPaymentGateway implements PaymentGateway
{
	public function __construct(
		private string $accountFile,
	) {
	}


	public function charge(string $card, int $amount): void
	{
		$balance = (int) file_get_contents($this->accountFile) - $amount;
		file_put_contents($this->accountFile, (string) $balance);
		echo "[RealPaymentGateway] charged \$$amount to card $card\n";
	}
}


class FakePaymentGateway implements PaymentGateway
{
	/** @var list<array{string, int}> */
	public array $charges = [];


	public function charge(string $card, int $amount): void
	{
		$this->charges[] = [$card, $amount];
		echo "[FakePaymentGateway] would have charged \$$amount to card $card\n";
	}
}


class CreditCard
{
	public function __construct(
		private string $number,
	) {
	}


	// ✅ The signature admits it: charging needs a gateway.
	public function charge(PaymentGateway $gateway, int $amount): void
	{
		$gateway->charge($this->number, $amount);
	}
}


// ---- the same test, written by the same clueless newcomer ----

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

	// ✅ You cannot call charge() without answering "which gateway?",
	// and the honest answer in a test is: a fake one, which also records.
	$gateway = new FakePaymentGateway;
	$card = new CreditCard('1234567890123456');
	$card->charge($gateway, 100);

	test('the gateway was asked for $100', $gateway->charges === [['1234567890123456', 100]]);
	test('no money moved', (int) file_get_contents($account) === $before);
}


testCreditCardCharge();

echo "\nThe test asserts more than the one in bad.php and costs nothing.\n";
echo "Not because I was careful. Because being careless was not an option.\n";
