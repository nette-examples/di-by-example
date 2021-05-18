<?php declare(strict_types=1);

// Somewhere in the project, someone defined this. You will not think about it again.
const LOG_DIR = __DIR__ . '/temp';


class Logger
{
	public function log(string $message): void
	{
		@mkdir(LOG_DIR); // @ - directory may already exist
		file_put_contents(LOG_DIR . '/app.log', $message . "\n", FILE_APPEND);
	}
}


// ⛔ Looks harmless. Two lines, no arguments, nothing to get wrong.
$logger = new Logger;
$logger->log('Newsletter sent to 1,200 subscribers');

echo "Logged something, somewhere.\n\n";
echo "Now answer these three questions without scrolling up to the class body:\n";
echo "  1. Which file did that message land in?\n";
echo "  2. What does Logger need in order to work at all?\n";
echo "  3. How do you make a second logger that writes audit records elsewhere?\n\n";
echo "You cannot answer any of them, and question 3 has no answer at all.\n";
echo "The signature `new Logger` promised the class needs nothing. It lied.\n";
