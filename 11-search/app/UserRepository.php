<?php declare(strict_types=1);

class UserRepository
{
	public function __construct(
		private Database $db,
	) {
	}


	public function count(): void
	{
		$this->db->query('SELECT count(*) FROM users');
	}
}
