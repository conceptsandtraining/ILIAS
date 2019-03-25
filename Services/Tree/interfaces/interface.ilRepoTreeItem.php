<?php
/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> */

declare(strict_types=1);

/**
 * Interface for a repo-tree-entry
 *
 */
interface RepositoryTreeItem
{
	public function getRefId(): int;
	public function getObjId(): int;
	public function getObjType(): string;
	public function getOwnerId(): int;

	public function getTitle(): string;
	public function getDescription(): string;

	public function getCreationDate(): \DateTime;
	public function getLastUpdateDate(): \DateTime;

	public function isOffline(): bool;
	public function isDeleted(): bool;
}
