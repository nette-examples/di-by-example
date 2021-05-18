<?php declare(strict_types=1);

class Logger
{
	// ✅ The class states what it needs. You cannot create it wrong.
	public function __construct(
		private string $file,
	) {
	}


	public function log(string $message): void
	{
		file_put_contents($this->file, $message . "\n", FILE_APPEND);
	}
}


@mkdir(__DIR__ . '/temp'); // @ - directory may already exist

$logger = new Logger(__DIR__ . '/temp/app.log');
$logger->log('Newsletter sent to 1,200 subscribers');

// The thing that was impossible a minute ago:
$audit = new Logger(__DIR__ . '/temp/audit.log');
$audit->log('Admin changed the pricing table');

echo "Logged to temp/app.log, audited to temp/audit.log.\n\n";
echo "Same three questions, answered by the signature alone:\n";
echo "  1. The file you passed in.\n";
echo "  2. A path to write to - it says so, right there in the constructor.\n";
echo "  3. `new Logger('...')` again, with a different path. Done, see above.\n\n";
echo "Nothing clever happened here. We just stopped hiding things.\n";
