<?php declare(strict_types=1);

class NewsletterDistributor
{
	public function __construct(
		private Mailer $mailer,
		private Logger $logger,
	) {
	}


	/** @param  string[]  $recipients */
	public function distribute(array $recipients): void
	{
		foreach ($recipients as $recipient) {
			$this->mailer->send($recipient, 'Our monthly newsletter');
		}
		$this->logger->log('sent ' . count($recipients) . ' newsletters');
	}
}
