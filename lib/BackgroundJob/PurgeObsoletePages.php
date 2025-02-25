<?php

declare(strict_types=1);

namespace OCA\Collectives\BackgroundJob;

use OCA\Collectives\Db\PageGarbageCollector;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;

class PurgeObsoletePages extends TimedJob {
	private PageGarbageCollector $garbageCollector;

	public function __construct(ITimeFactory $time,
		PageGarbageCollector $garbageCollector) {
		parent::__construct($time);

		// Run once every two days
		$this->setInterval(60 * 60 * 24 * 2);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);

		$this->garbageCollector = $garbageCollector;
	}

	/**
	 * @param $argument
	 */
	protected function run($argument): void {
		$this->garbageCollector->purgeObsoletePages();
	}
}
