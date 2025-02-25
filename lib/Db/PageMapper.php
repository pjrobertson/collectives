<?php

declare(strict_types=1);

namespace OCA\Collectives\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @method Page insert(Entity $page)
 * @method Page update(Entity $page)
 * @method Page delete(Entity $page)
 * @method Page findEntity(IQueryBuilder $query)
 * @template-extends QBMapper<Page>
 */
class PageMapper extends QBMapper {
	/**
	 * PageMapper constructor.
	 *
	 * @param IDBConnection $db
	 * @param string|null   $entityClass
	 */
	public function __construct(IDBConnection $db, string $entityClass = null) {
		parent::__construct($db, 'collectives_pages', $entityClass);
	}

	/**
	 * @param Page $page
	 *
	 * @return Page
	 */
	public function updateOrInsert(Page $page): Page {
		if (null === $page->getId() &&
			null !== $oldPage = $this->findByFileId($page->getFileId())) {
			$page->setId($oldPage->getId());
			return $this->update($page);
		}

		return $this->insert($page);
	}

	/**
	 * @param int $fileId
	 *
	 * @return Page|null
	 */
	public function trashByFileId(int $fileId): ?Page {
		if (null !== $page = $this->findByFileId($fileId)) {
			$page->setTrashTimestamp(time());
			return $this->update($page);
		}
		return null;
	}

	/**
	 * @param int $fileId
	 *
	 * @return Page|null
	 */
	public function restoreByFileId(int $fileId): ?Page {
		if (null !== $page = $this->findByFileId($fileId, true)) {
			$page->setTrashTimestamp(null);
			return $this->update($page);
		}
		return null;
	}

	/**
	 * @param int $fileId
	 *
	 * @return Page|null
	 */
	public function deleteByFileId(int $fileId): ?Page {
		if (null !== $page = $this->findByFileId($fileId, true)) {
			return $this->delete($page);
		}
		return null;
	}

	/**
	 * @param int  $fileId
	 * @param bool $trashed
	 *
	 * @return Page|null
	 */
	public function findByFileId(int $fileId, bool $trashed = false): ?Page {
		$qb = $this->db->getQueryBuilder();
		$where = $qb->expr()->andX();
		$where->add($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		if ($trashed) {
			$where->add($qb->expr()->isNotNull('trash_timestamp'));
		} else {
			$where->add($qb->expr()->isNull('trash_timestamp'));
		}
		$qb->select('*')
			->from($this->tableName)
			->where($where);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
			return null;
		}
	}

	/**
	 * @return Page[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName);
		return $this->findEntities($qb);
	}
}
