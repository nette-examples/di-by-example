<?php declare(strict_types=1);

class Article
{
	public string $title = '';


	public function __construct(
		private Database $db,
		private SlugGenerator $slugs,
		private int $authorId,
	) {
	}


	public function save(): void
	{
		$slug = $this->slugs->generate($this->title);
		$this->db->query("INSERT INTO articles (title, slug, author) VALUES ('$this->title', '$slug', $this->authorId)");
	}
}
