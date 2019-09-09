<?php
/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> */

declare(strict_types=1);

/**
 * Wrapper for ilTree
 *
 */
class ilRepositoryTree implements RepositoryTree
{
	/**
	 * @var ilTree
	 */
	protected $tree;

	public function __construct(ilTree $tree)
	{
		$this->tree = $tree;
	}

	protected function buildTreeItem(array $node_data): ilRepositoryTreeItem
	{
		$creation_date = new \DateTime($node_data['create_date']);
		$last_update = new \DateTime($node_data['last_update']);

		$offline = false;
		if($offline === '1') {
			$offline = true;
		}

		$deleted = false;
		if($deleted === '1') {
			$deleted = true;
		}

		return new ilRepositoryTreeItem(
			(int)$node_data['ref_id'],
			(int)$node_data['obj_id'],
			$node_data['type'],
			(int)$node_data['owner'],
			$node_data['title'],
			$node_data['description'],
			$creation_date,
			$last_update,
			$offline,
			$deleted
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildren(int $ref_id): array
	{
		return array_map(
			[$this, 'buildTreeItem'],
			$this->tree->getChilds($ref_id)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildrenIds(int $ref_id): array
	{
		return array_map('intval', $this->tree->getChildIds($ref_id));
	}

	/**
	 * @inheritdoc
	 */
	public function getParent(int $ref_id): RepositoryTreeItem
	{
		return $this->buildTreeItem($this->tree->getParentNodeData($ref_id));
	}

	/**
	 * @inheritdoc
	 */
	public function getParentId(int $ref_id): int
	{
		return (int)$this->tree->getParentId($ref_id);
	}

	/**
	 * @inheritdoc
	 */
	public function getAllChildrenByType(int $ref_id, string $search_type): array
	{
		$childs = $this->tree->getSubTree(
			$this->tree->getNodeData($ref_id),
			true,
			$search_type
		);
		return array_map([$this, 'buildTreeItem'], $childs);
	}

	/**
	 * @inheritdoc
	 */
	public function getParentByType(int $ref_id, string $search_type)
	{
		foreach($this->tree->getPathFull($ref_id) as $hop) {
			if($hop['type'] === $search_type) {
				return $this->buildTreeItem($hop);
			}
		}
		return false;
	}

}
