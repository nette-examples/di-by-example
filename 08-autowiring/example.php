<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'Cache', 'ArticleRepository', 'Shipper', 'DhlShipper', 'PplShipper', 'ShipManager'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

// ArticleRepository asked for Database, Logger and an optional Cache.
// It got mainDb and the logger; there is no Cache service, so the default stayed.
$container->getByType(ArticleRepository::class)->save('Ten things about dependency injection');

// ShipManager asked for Shipper[] and received every shipper in the container
$container->getByType(ShipManager::class)->shipAll('order #42');

// The hidden database is still reachable by name when you really want it.
// Note that getService() can only promise an object - a name carries no type.
echo "\ntempDb is excluded from autowiring, but not from existence:\n";
$tempDb = $container->getService('tempDb');
if ($tempDb instanceof Database) {
	$tempDb->query('SELECT 1');
}
