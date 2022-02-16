<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'FileLogger', 'Mailer', 'SmtpMailer', 'NewsletterDistributor'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


@mkdir(__DIR__ . '/temp'); // @ - directory may already exist

$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	// Parameters can also come from PHP - here we add one the config refers to
	$compiler->addConfig(['parameters' => ['tempDir' => __DIR__ . '/temp']]);
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

$container->getByType(NewsletterDistributor::class)
	->distribute(['alice@example.com', 'bob@example.com']);
