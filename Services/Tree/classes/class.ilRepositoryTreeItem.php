<?php
/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> */

declare(strict_types=1);

/**
 * Wrapper for ilTree
 *
 */
class ilRepositoryTreeItem implements RepositoryTreeItem
{
	public function __construct(
		int $ref_id,
		int $obj_id,
		string $obj_type,
		int $owner_id,
		string $title,
		string $description,
		\DateTime $creation_date,
		\DateTime $last_update,
		bool $offline,
		bool $deleted
	) {
		$this->ref_id = $ref_id;
		$this->obj_id = $obj_id;
		$this->obj_type = $obj_type;
		$this->owner_id = $owner_id;
		$this->title = $title;
		$this->description = $description;
		$this->creation_date = $creation_date;
		$this->last_update = $last_update;
		$this->offline = $offline;
		$this->deleted = $deleted;
	}

	public function getRefId(): int
	{
		return $this->ref_id;
	}

	public function getObjId(): int
	{
		return $this->obj_id;
	}

	public function getObjType(): string
	{
		return $this->obj_type;
	}

	public function getOwnerId(): int
	{
		return $this->owner_id;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getCreationDate(): \DateTime
	{
		return $this->creation_date;
	}

	public function getLastUpdateDate(): \DateTime
	{
		return $this->last_update;
	}

	public function isOffline(): bool
	{
		return $this->offline;
	}

	public function isDeleted(): bool
	{
		return $this->deleted;
	}

}
