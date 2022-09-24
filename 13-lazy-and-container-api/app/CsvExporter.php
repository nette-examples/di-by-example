<?php declare(strict_types=1);

// Deliberately not registered in config.neon - the container will still
// build it on request and fill its constructor from what it does manage.
class CsvExporter
{
	public function __construct(
		private Database $db,
		private Logger $logger,
	) {
	}


	public function export(): void
	{
		$this->db->query('SELECT * FROM articles');
		$this->logger->log('exported to CSV');
	}
}
