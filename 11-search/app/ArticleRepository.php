<?php declare(strict_types=1);

class ArticleRepository
{
	public function __construct(
		private Database $db,
		private Logger $logger,
	) {
	}


	public function save(string $title): void
	{
		$this->db->query("INSERT INTO articles (title) VALUES ('$title')");
		$this->logger->log("article saved: $title");
	}
}
