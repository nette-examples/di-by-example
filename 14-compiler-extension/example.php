<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'EventHandler', 'SendWelcomeEmail', 'UpdateStatistics', 'EventDispatcher', 'EventsExtension'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	// Our extension owns the "events" section of the configuration
	$compiler->addExtension('events', new EventsExtension);
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

echo "\nThe container is built. Dispatching:\n";
$container->getByType(EventDispatcher::class)->dispatch('user.registered');

echo "\nThe extension line above appeared only on the run that compiled the\n";
echo "container. Delete temp/ and run again to see it once more.\n";
