<?php declare(strict_types=1);

@mkdir(__DIR__ . '/temp'); // @ - directory may already exist


class FileLogger
{
	public function __construct(
		private string $file,
		private string $prefix,
	) {
	}


	public function log(string $message): void
	{
		file_put_contents($this->file, "$this->prefix $message\n", FILE_APPEND);
	}
}


class NewsletterDistributor
{
	// ⛔ Neither of these belongs to me. I do not write to files.
	// I am only holding them because the logger will want them later.
	public function __construct(
		private string $logFile,
		private string $logPrefix,
	) {
	}


	/** @param  string[]  $recipients */
	public function distribute(array $recipients): void
	{
		$logger = new FileLogger($this->logFile, $this->logPrefix);
		foreach ($recipients as $recipient) {
			echo "[Mail] to $recipient\n";
		}
		$logger->log('sent ' . count($recipients) . ' newsletters');
	}
}


$distributor = new NewsletterDistributor(__DIR__ . '/temp/app.log', '[newsletter]');
$distributor->distribute(['alice@example.com', 'bob@example.com']);

echo "\nIt works. Now try two things I actually needed last week:\n";
echo "  1. See the log in the console while developing, not in a file.\n";
echo "  2. Add a third argument to FileLogger, say a minimum level.\n\n";
echo "The first is impossible: the distributor builds its own FileLogger.\n";
echo "The second means editing NewsletterDistributor, a class that does not log.\n";


// ---- and now a test, which is where the design sends its bill ----

// The whole testing framework this course needs.
function test(string $expectation, bool $passed): void
{
	echo ($passed ? '[PASS] ' : '[FAIL] ') . $expectation . "\n";
	if (!$passed) {
		exit(1);
	}
}


$log = __DIR__ . '/temp/app.log';
@unlink($log); // no seam, so the test has to wipe the application's real log first

echo "\nTesting that distribute() logs:\n";
new NewsletterDistributor($log, '[test]')->distribute(['carol@example.com']);
test('the newsletter was logged', str_contains((string) file_get_contents($log), 'sent 1'));

echo "\nGreen - but to observe one method call the test had to know a file path,\n";
echo "touch the disk, and delete the file the application logs into.\n";
