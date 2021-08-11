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


// ✅ What used to be the base class is now a thing you can own.
class PageRenderer
{
	public function __construct(
		private Cache $cache,
		private Translator $translator,
	) {
	}


	public function say(string $text): string
	{
		return $this->cache->load($text)
			?? $this->translator->translate($text);
	}
}


class ProductController
{
	// ✅ Every argument is mine. Nothing is being carried upstairs.
	public function __construct(
		private PageRenderer $renderer,
		private Database $db,
	) {
	}


	public function show(): void
	{
		echo $this->renderer->say('product page'), "\n";
		$this->db->query('SELECT * FROM products');
	}
}


class OrderController
{
	public function __construct(
		private PageRenderer $renderer,
		private Mailer $mailer,
	) {
	}


	public function checkout(): void
	{
		echo $this->renderer->say('order page'), "\n";
		$this->mailer->send('confirmation');
	}
}


$renderer = new PageRenderer(new Cache, new Translator);

new ProductController($renderer, new Database)->show();
new OrderController($renderer, new Mailer)->checkout();

echo "\nNow add request logging to PageRenderer: one new constructor argument,\n";
echo "in one class. The controllers do not change. They never even find out.\n";
