<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'FileLogger', 'Mailer', 'SmtpMailer', 'FakeMailer', 'NewsletterDistributor'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


// The whole testing framework this course needs.
function test(string $expectation, bool $passed): void
{
	echo ($passed ? '[PASS] ' : '[FAIL] ') . $expectation . "\n";
	if (!$passed) {
		exit(1);
	}
}


@mkdir(__DIR__ . '/temp'); // @ - directory may already exist

// The same three lines as example.php, pointed at test.neon. The second
// argument to load() keeps the two compiled containers apart in temp/.
$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->addConfig(['parameters' => ['tempDir' => __DIR__ . '/temp']]);
	$compiler->loadConfig(__DIR__ . '/test.neon');
	return null;
}, 'test');

$container = new $class;

$mailer = $container->getByType(Mailer::class);
$container->getByType(NewsletterDistributor::class)
	->distribute(['alice@example.com', 'bob@example.com']);

test(
	'the fake mailer received both recipients',
	$mailer instanceof FakeMailer && $mailer->sent === ['alice@example.com', 'bob@example.com'],
);

echo "\nNo [SmtpMailer] line above: the class under test never knew.\n";
