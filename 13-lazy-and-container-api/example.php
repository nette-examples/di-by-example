<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'ReportGenerator', 'CsvExporter'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->addExtension('di', new Nette\DI\Extensions\DIExtension);
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});

$container = new $class;

echo "Container built. Nothing has connected to anything yet.\n";

$report = $container->getByType(ReportGenerator::class);
echo 'Got a ReportGenerator. Does the database service exist yet? ';
echo $container->isCreated('database') ? "yes\n" : "no - the generator is a sleeping proxy and has not asked for one\n";

// A method that touches no property leaves the proxy asleep
echo "\nCalling ping(), which reads no property:\n";
$container->getByType(Database::class)->ping();
echo 'isCreated() now says ', $container->isCreated('database') ? 'yes' : 'no';
echo " - the proxy exists, but its constructor has not run.\n";

echo "\nNow doing actual work:\n";
$report->monthly();

echo "\nAnd now? ";
echo $container->isCreated('database') ? "created\n" : "still not\n";

// A few other things the container can tell you or do for you
echo "\nhasService('database'): ", var_export($container->hasService('database'), true), "\n";
echo 'getParameters(): ', json_encode($container->getParameters()), "\n";

// CsvExporter is not in config.neon, yet its constructor gets filled in
echo "\ncreateInstance() builds an unregistered class with its dependencies:\n";
$container->createInstance(CsvExporter::class)->export();
