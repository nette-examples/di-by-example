<?php declare(strict_types=1);

class ReportGenerator
{
	public function __construct(
		private Database $db,
		private Logger $logger,
	) {
	}


	public function monthly(): void
	{
		$this->db->query('SELECT count(*) FROM articles');
		$this->logger->log('monthly report generated');
	}
}
