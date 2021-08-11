<?php declare(strict_types=1);

class Cache
{
	public function load(string $key): ?string
	{
		return null;
	}
}


class Translator
{
	public function translate(string $text): string
	{
		return strtoupper($text);
	}
}


abstract class BaseController
{
	public function __construct(
		private Cache $cache,
		private Translator $translator,
	) {
	}


	protected function say(string $text): string
	{
		return $this->cache->load($text)
			?? $this->translator->translate($text);
	}
}


class ProductController extends BaseController
{
	// ⛔ Two of these three arguments are not mine. I am only passing them upstairs.
	public function __construct(
		Cache $cache,
		Translator $translator,
		private Database $db,
	) {
		parent::__construct($cache, $translator);
	}


	public function show(): void
	{
		echo $this->say('product page'), "\n";
		$this->db->query('SELECT * FROM products');
	}
}


class OrderController extends BaseController
{
	// ⛔ ...and again here. And in every other controller in the project.
	public function __construct(
		Cache $cache,
		Translator $translator,
		private Mailer $mailer,
	) {
		parent::__construct($cache, $translator);
	}


	public function checkout(): void
	{
		echo $this->say('order page'), "\n";
		$this->mailer->send('confirmation');
	}
}


class Database
{
	public function query(string $sql): void
	{
		echo "[Database] $sql\n";
	}
}

class Mailer
{
	public function send(string $subject): void
	{
		echo "[Mailer] sending $subject\n";
	}
}


$cache = new Cache;
$translator = new Translator;

new ProductController($cache, $translator, new Database)->show();
new OrderController($cache, $translator, new Mailer)->checkout();

echo "\nNow the product owner wants request logging in every controller.\n";
echo "That means one new argument in BaseController - and then editing\n";
echo "every single child constructor, plus every place that creates one.\n";
echo "Two controllers here. In that project there were forty.\n";
