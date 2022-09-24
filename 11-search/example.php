<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'ArticleRepository', 'UserRepository', 'TagRepository'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	// Standalone Compiler enables only services and parameters,
	// so the search section needs its extension registered by hand.
	$compiler->addExtension('search', new Nette\DI\Extensions\SearchExtension(__DIR__ . '/temp'));
	$compiler->addConfig(['parameters' => ['appDir' => __DIR__ . '/app']]);
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

// Nothing below was listed in the configuration by name
$container->getByType(ArticleRepository::class)->save('Ten things about dependency injection');
$container->getByType(UserRepository::class)->count();
$container->getByType(TagRepository::class)->popular();

echo "\nThree repositories, zero lines of configuration each.\n";
