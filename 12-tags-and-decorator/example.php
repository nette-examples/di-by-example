<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'EventHandler', 'SendWelcomeEmail', 'UpdateStatistics', 'EventDispatcher'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->addExtension('decorator', new Nette\DI\Extensions\DecoratorExtension);
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

// The dispatcher received every service tagged "subscriber",
// and each of them was set up by the decorator.
$container->getByType(EventDispatcher::class)->dispatch('user.registered');

// Tags are readable at runtime too, values included
echo "\nServices tagged 'subscriber': ";
echo json_encode($container->findByTag('subscriber')), "\n";
