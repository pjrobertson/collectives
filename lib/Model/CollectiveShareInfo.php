<?php

declare(strict_types=1);

namespace OCA\Collectives\Model;

use OCA\Collectives\Db\CollectiveShare;

/**
 * @method bool getEditable()
 * @method void setEditable(bool $editable)
 */
class CollectiveShareInfo extends CollectiveShare {
	protected bool $editable;

	public function __construct(CollectiveShare $collectiveShare,
		bool $editable = false) {
		$this->id = $collectiveShare->getId();
		$this->collectiveId = $collectiveShare->getCollectiveId();
		$this->pageId = $collectiveShare->getPageId();
		$this->token = $collectiveShare->getToken();
		$this->owner = $collectiveShare->getOwner();
		$this->editable = $editable;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'collectiveId' => $this->collectiveId,
			'pageId' => $this->pageId,
			'token' => $this->token,
			'owner' => $this->owner,
			'editable' => $this->editable,
		];
	}
}
