<?php declare(strict_types=1);

// Order matters: an interface must be loaded before its implementations
foreach (['Logger', 'ConsoleLogger', 'Database', 'ArticleRepository', 'ReportGenerator'] as $class) {
	require_once __DIR__ . "/app/$class.php";
}


// ✅ One class that knows how the whole application is wired together.
class Container
{
	private ?Database $database = null;
	private ?Logger $logger = null;
	private ?ArticleRepository $articleRepository = null;
	private ?ReportGenerator $reportGenerator = null;


	/** @param  string[]  $parameters */
	public function __construct(
		private array $parameters,
	) {
	}


	// Every method has the same shape: build on first call, reuse ever after.
	// Everything the container hands out is a shared instance - a service.
	public function getDatabase(): Database
	{
		return $this->database ??= new Database($this->parameters['dsn'], $this->parameters['user']);
	}


	public function getLogger(): Logger
	{
		return $this->logger ??= new ConsoleLogger;
	}


	public function getArticleRepository(): ArticleRepository
	{
		return $this->articleRepository ??= new ArticleRepository($this->getDatabase(), $this->getLogger());
	}


	public function getReportGenerator(): ReportGenerator
	{
		return $this->reportGenerator ??= new ReportGenerator($this->getDatabase(), $this->getLogger());
	}
}


$container = new Container([
	'dsn' => 'mysql:host=127.0.0.1;dbname=blog',
	'user' => 'admin',
]);

$container->getArticleRepository()->save('Ten things about dependency injection');
$container->getReportGenerator()->monthly();

echo "\nOne connection this time, and neither class knows the container exists.\n";
echo "Look at ArticleRepository: unchanged since bad.php. That is the deal -\n";
echo "the container knows about the classes, the classes know nothing about it.\n";
