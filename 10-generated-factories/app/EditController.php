<?php declare(strict_types=1);

class EditController
{
	public function __construct(
		private ArticleFactory $articleFactory,
		private Logger $logger,
	) {
	}


	public function submit(string $title, int $authorId): void
	{
		$article = $this->articleFactory->create($authorId);
		$article->title = $title;
		$article->save();
		$this->logger->log("article submitted by author $authorId");
	}
}
