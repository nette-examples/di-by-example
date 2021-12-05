<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'ArticleRepository', 'ReportGenerator'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


// Compiles config.neon into a PHP class and caches it in temp/.
// autoRebuild: recompile whenever the config or a class changes.
$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

// Ask for services by type - no strings, no keys to misspell
$container->getByType(ArticleRepository::class)->save('Ten things about dependency injection');
$container->getByType(ReportGenerator::class)->monthly();

echo "\nOne connection, same as chapter 06 - but nobody wrote a Container class.\n";
echo "It was generated. Open temp/ and read it: it is the file you wrote by hand.\n";
