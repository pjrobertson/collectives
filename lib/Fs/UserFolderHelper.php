<?php

declare(strict_types=1);

namespace OCA\Collectives\Fs;

use OC\User\NoUserException;
use OCA\Collectives\Service\NotFoundException;
use OCA\Collectives\Service\NotPermittedException;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException as FilesNotFoundException;
use OCP\Files\NotPermittedException as FilesNotPermittedException;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;

class UserFolderHelper {
	private IRootFolder $rootFolder;
	private IUserManager $userManager;
	private IConfig $config;
	private IFactory $l10nFactory;
	private ?Folder $userCollectivesFolder = null;

	/**
	 * UserFolderHelper constructor.
	 *
	 * @param IRootFolder  $rootFolder
	 * @param IUserManager $userManager
	 * @param IConfig      $config
	 * @param IFactory     $l10nFactory
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		IConfig $config,
		IFactory $l10nFactory) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->config = $config;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * @param $userId
	 *
	 * @return string
	 * @throws NotPermittedException
	 */
	public function getUserFolderSetting($userId): string {
		$defaultUserFolder = $this->config->getAppValue('collectives', 'default_user_folder', '');
		// Get collectives user folder from settings and default to translated 'Collectives'
		$userCollectivesPath = $this->config->getUserValue($userId, 'collectives', 'user_folder', $defaultUserFolder);
		if ($userCollectivesPath === '') {
			$user = $this->userManager->get($userId);

			// Guest users and others with null quota are not allowed to create a subdirectory
			if ($user->getQuota() === '0 B') {
				return '/';
			}

			$userLang = $this->l10nFactory->getUserLanguage($user);
			$l10n = $this->l10nFactory->get('collectives', $userLang);
			$userCollectivesPath = '/' . $l10n->t('Collectives');
			try {
				$this->config->setUserValue($userId, 'collectives', 'user_folder', $userCollectivesPath);
			} catch (PreConditionNotMetException | \UnexpectedValueException $e) {
				throw new NotPermittedException($e->getMessage(), 0, $e);
			}
		}

		return $userCollectivesPath;
	}

	/**
	 * @param string $userId
	 *
	 * @return Folder
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	private function initialize(string $userId): Folder {
		try {
			$userFolder = $this->rootFolder->getUserFolder($userId);
		} catch (FilesNotPermittedException | NoUserException $e) {
			throw new NotPermittedException($e->getMessage(), 0, $e);
		}

		$userCollectivesPath = $this->getUserFolderSetting($userId);
		// If collectives path is empty (due to null quota), return userFolder
		if ($userCollectivesPath === '/') {
			return $userFolder;
		}

		try {
			$userCollectivesFolder = $userFolder->get($userCollectivesPath);
			// Rename existing node if it's not a folder
			if (!$userCollectivesFolder instanceof Folder) {
				$new = NodeHelper::generateFilename($userFolder, $userCollectivesPath);
				$userCollectivesFolder->move($userFolder->getPath() . '/' . $new);
				$userCollectivesFolder = $userFolder->newFolder($userCollectivesPath);
			}
		} catch (FilesNotFoundException $e) {
			try {
				$userCollectivesFolder = $userFolder->newFolder($userCollectivesPath);
			} catch (FilesNotPermittedException $e) {
				throw new NotPermittedException($e->getMessage(), 0, $e);
			}
		} catch (InvalidPathException $e) {
			throw new NotFoundException($e->getMessage(), 0, $e);
		} catch (FilesNotPermittedException | LockedException $e) {
			throw new NotPermittedException($e->getMessage(), 0, $e);
		}

		return $userCollectivesFolder;
	}

	/**
	 * @param string $userId
	 *
	 * @return Folder
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function get(string $userId): Folder {
		if (!$this->userCollectivesFolder) {
			$this->userCollectivesFolder = $this->initialize($userId);
		}

		return $this->userCollectivesFolder;
	}
}
