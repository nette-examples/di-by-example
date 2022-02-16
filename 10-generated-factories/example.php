<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'SlugGenerator', 'Article', 'ArticleFactory', 'DatabaseAccessor', 'EditController'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

// EditController received a working ArticleFactory nobody implemented
$controller = $container->getByType(EditController::class);
$controller->submit('Ten things about dependency injection', authorId: 7);
$controller->submit('Eleven things about dependency injection', authorId: 42);

// An accessor is the other half of the idea: it hands out a shared service
$accessor = $container->getByType(DatabaseAccessor::class);
$first = $accessor->get();
$second = $accessor->get();

echo "\nFactory create() built two different Articles above.\n";
echo 'Accessor get() called twice returns the same Database: ';
echo $first === $second ? "yes\n" : "no\n";
$first->query('SELECT count(*) FROM articles');
