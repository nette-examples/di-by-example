<?php declare(strict_types=1);

class TagRepository
{
	public function __construct(
		private Database $db,
	) {
	}


	public function popular(): void
	{
		$this->db->query('SELECT tag FROM tags ORDER BY uses DESC LIMIT 10');
	}
}
