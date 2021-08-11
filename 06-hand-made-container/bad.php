<?php declare(strict_types=1);

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'ArticleRepository', 'ReportGenerator'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


// ---- the editing part of the application ----

$db = new Database('mysql:host=127.0.0.1;dbname=blog', 'admin');
$logger = new ConsoleLogger;
$repository = new ArticleRepository($db, $logger);
$repository->save('Ten things about dependency injection');


// ---- the reporting part, written a month later by someone else ----

// ⛔ They needed the same things, so they wrote the same lines again.
$db = new Database('mysql:host=127.0.0.1;dbname=blog', 'admin');
$logger = new ConsoleLogger;
$report = new ReportGenerator($db, $logger);
$report->monthly();


echo "\nCount the connections above: two. One process, one database, two connections.\n";
echo "Nobody did anything stupid - the wiring simply lives in two places,\n";
echo "and there is nothing anywhere that says it should live in one.\n";
