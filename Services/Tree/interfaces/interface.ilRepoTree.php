<?php
/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> */

declare(strict_types=1);

/**
 * Interface for a repo-tree
 *
 */
interface RepositoryTree
{
	/**
	 * @return RepositoryTreeItem[]
	 */
	public function getChildren(int $ref_id): array;

	/**
	 * @return int[]
	 */
	public function getChildrenIds(int $ref_id): array;

	/**
	 * @return RepositoryTreeItem
	 */
	public function getParent(int $ref_id): RepositoryTreeItem;

	/**
	 * @return int
	 */
	public function getParentId(int $ref_id): int;

	/**
	 * deep search
	 * @return RepositoryTreeItem[]
	 */
	public function getAllChildrenByType(int $ref_id, string $search_type): array;

	/**
	 * deep search
	 * @return RepositoryTreeItem|false
	 */
	public function getParentByType(int $ref_id, string $search_type);

}
