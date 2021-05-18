<?php declare(strict_types=1);

@mkdir(__DIR__ . '/temp'); // @ - directory may already exist


// Note the name: Logger, not ILogger, not LoggerInterface.
interface Logger
{
	public function log(string $message): void;
}


class FileLogger implements Logger
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


class ConsoleLogger implements Logger
{
	public function log(string $message): void
	{
		echo "[log] $message\n";
	}
}


class NewsletterDistributor
{
	// ✅ A logger is mine. Whatever the logger itself needs is its own business.
	public function __construct(
		private Logger $logger,
	) {
	}


	/** @param  string[]  $recipients */
	public function distribute(array $recipients): void
	{
		foreach ($recipients as $recipient) {
			echo "[Mail] to $recipient\n";
		}
		$this->logger->log('sent ' . count($recipients) . ' newsletters');
	}
}


$recipients = ['alice@example.com', 'bob@example.com'];

$distributor = new NewsletterDistributor(new FileLogger(__DIR__ . '/temp/app.log', '[newsletter]'));
$distributor->distribute($recipients);

echo "\n--- same distributor class, different logger ---\n\n";

$distributor = new NewsletterDistributor(new ConsoleLogger);
$distributor->distribute($recipients);

echo "\nNewsletterDistributor was not touched between those two runs.\n";
echo "It never knew there was a file, a prefix, or a second implementation.\n";


// ---- and now the same test, for free ----

// The whole testing framework this course needs.
function test(string $expectation, bool $passed): void
{
	echo ($passed ? '[PASS] ' : '[FAIL] ') . $expectation . "\n";
	if (!$passed) {
		exit(1);
	}
}


// A third implementation, written in four lines, that remembers instead of writing.
class FakeLogger implements Logger
{
	/** @var string[] */
	public array $messages = [];


	public function log(string $message): void
	{
		$this->messages[] = $message;
	}
}


echo "\nTesting that distribute() logs:\n";
$fake = new FakeLogger;
new NewsletterDistributor($fake)->distribute(['carol@example.com']);
test('the newsletter was logged', $fake->messages === ['sent 1 newsletters']);

echo "\nNo file, no path, no cleanup, nothing left behind. The seam that made\n";
echo "this possible is the constructor - the same one that fixed the design.\n";
