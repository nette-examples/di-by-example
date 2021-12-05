<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'Cache', 'ArticleRepository'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


// This configuration cannot be compiled. Let's look at how it fails.
$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);

try {
	$class = $loader->load(function (Nette\DI\Compiler $compiler) {
		$compiler->loadConfig(__DIR__ . '/conflict.neon');
		return null;
	}, 'conflict');
	new $class;

} catch (Nette\DI\ServiceCreationException $e) {
	echo "Compilation failed, and here is what it said:\n\n";
	echo $e->getMessage(), "\n\n";
	echo "Note when this happened: at compile time, before a single service existed.\n";
	echo "Not on a Tuesday in production when someone finally hit that code path.\n";
}
