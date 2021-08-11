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


class Article
{
	public string $title = '';


	public function __construct(
		private Database $db,
	) {
	}


	public function save(): void
	{
		$this->db->query("INSERT INTO articles (title) VALUES ('$this->title')");
	}
}


class EditController
{
	// ⛔ I do not use the database. I only hold it so I can build Articles.
	public function __construct(
		private Database $db,
	) {
	}


	public function submit(string $title): void
	{
		$article = new Article($this->db);
		$article->title = $title;
		$article->save();
	}
}


$db = new Database('mysql:host=127.0.0.1;dbname=blog');
new EditController($db)->submit('Ten things about dependency injection');

echo "\nThe controller never queries anything. It carries a Database around\n";
echo "for one reason: `new Article(...)` demands it.\n\n";
echo "So the day Article needs a second dependency - a slug generator, say -\n";
echo "the change lands in EditController. And in every other class that\n";
echo "creates an Article. None of which have anything to do with slugs.\n";
