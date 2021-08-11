<?php declare(strict_types=1);

class Database
{
	public function __construct(
		private string $dsn,
	) {
		echo "[Database] connected to $this->dsn\n";
	}


	public function query(string $sql): void
	{
		echo "[Database] $sql\n";
	}
}


class SlugGenerator
{
	public function generate(string $title): string
	{
		return strtolower(str_replace(' ', '-', $title));
	}
}


class Article
{
	public string $title = '';


	public function __construct(
		private Database $db,
		private SlugGenerator $slugs,
	) {
	}


	public function save(): void
	{
		$slug = $this->slugs->generate($this->title);
		$this->db->query("INSERT INTO articles (title, slug) VALUES ('$this->title', '$slug')");
	}
}


// ✅ One place in the whole application knows how to build an Article.
class ArticleFactory
{
	public function __construct(
		private Database $db,
		private SlugGenerator $slugs,
	) {
	}


	public function create(): Article
	{
		return new Article($this->db, $this->slugs);
	}
}


class EditController
{
	// ✅ I create Articles, so I ask for the thing that creates Articles.
	public function __construct(
		private ArticleFactory $articleFactory,
	) {
	}


	public function submit(string $title): void
	{
		$article = $this->articleFactory->create();
		$article->title = $title;
		$article->save();
	}
}


$db = new Database('mysql:host=127.0.0.1;dbname=blog');
$factory = new ArticleFactory($db, new SlugGenerator);

new EditController($factory)->submit('Ten things about dependency injection');

echo "\nArticle gained a second dependency since bad.php - and EditController\n";
echo "did not change at all. Only ArticleFactory knows how an Article is built.\n";
