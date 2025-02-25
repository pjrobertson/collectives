<?php

declare(strict_types=1);

namespace OCA\Collectives\Service;

use OCA\Collectives\Db\CollectiveMapper;
use OCA\Collectives\Db\CollectiveUserSettings;
use OCA\Collectives\Db\CollectiveUserSettingsMapper;
use OCP\DB\Exception;

class CollectiveUserSettingsService {
	private CollectiveUserSettingsMapper $collectiveUserSettingsMapper;
	private CollectiveMapper $collectiveMapper;

	public function __construct(CollectiveUserSettingsMapper $collectiveUserSettingsMapper,
		CollectiveMapper $collectiveMapper) {
		$this->collectiveUserSettingsMapper = $collectiveUserSettingsMapper;
		$this->collectiveMapper = $collectiveMapper;
	}

	/**
	 * @param int    $collectiveId
	 * @param string $userId
	 *
	 * @return CollectiveUserSettings
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	private function initSettings(int $collectiveId, string $userId): CollectiveUserSettings {
		if (null === $this->collectiveMapper->findByIdAndUser($collectiveId, $userId)) {
			throw new NotFoundException('Collective not found: ' . $collectiveId);
		}

		$settings = $this->collectiveUserSettingsMapper->findByCollectiveAndUser($collectiveId, $userId);
		if ($settings === null) {
			$settings = new CollectiveUserSettings();
			$settings->setCollectiveId($collectiveId);
			$settings->setUserId($userId);
		}
		return $settings;
	}

	/**
	 * @param int    $collectiveId
	 * @param string $userId
	 * @param int    $pageOrder
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function setPageOrder(int $collectiveId, string $userId, int $pageOrder): void {
		$settings = $this->initSettings($collectiveId, $userId);
		$settings->setPageOrder($pageOrder);

		try {
			$this->collectiveUserSettingsMapper->insertOrUpdate($settings);
		} catch (Exception $e) {
			throw new NotPermittedException($e->getMessage(), 0, $e);
		}
	}

	/**
	 * @param int    $collectiveId
	 * @param string $userId
	 * @param bool   $showRecentPages
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function setShowRecentPages(int $collectiveId, string $userId, bool $showRecentPages): void {
		$settings = $this->initSettings($collectiveId, $userId);
		$settings->setShowRecentPages($showRecentPages);

		try {
			$this->collectiveUserSettingsMapper->insertOrUpdate($settings);
		} catch (Exception $e) {
			throw new NotPermittedException($e->getMessage(), 0, $e);
		}
	}
}
