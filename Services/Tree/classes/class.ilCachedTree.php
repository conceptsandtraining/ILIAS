<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Uses global cache to optimize queries on tree.
 */
class ilCachedTree extends ilTree
{
	/**
	 * @var	\ilTree
	 */
	protected $other;

	/**
	 * @var \ilGlobalCache
	 */
	protected $global_cache;

	/**
	* Constructor
	*/
	public function __construct(\ilTree $other, \ilGlobalCache $global_cache)
	{
		$this->other = $other;
		$this->global_cache = $global_cache;
	}
	
	/**
	 * Init tree implementation
	 */
	public function initTreeImplementation()
	{
		return $this->other->initTreeImplementation();
	}
	
	/**
	 * Get tree implementation
	 * @return ilTreeImplementation $impl
	 */
	public function getTreeImplementation()
	{
		return $this->other->getTreeImplementation();
	}
	
	/**
	* Use Cache (usually activated)
	*/
	public function useCache($a_use = true)
	{
		return $this->other->useCache($a_use);
	}
	
	/**
	 * Check if cache is active
	 * @return bool
	 */
	public function isCacheUsed()
	{
		return $this->other->isCacheUsed();
	}
	
	/**
	 * Get depth cache
	 * @return type
	 */
	public function getDepthCache()
	{
		return $this->other->getDepthCache();
	}
	
	/**
	 * Get parent cache
	 * @return type
	 */
	public function getParentCache()
	{
		return $this->other->getParentCache();
	}
	
	/**
	* Store user language. This function is used by the "main"
	* tree only (during initialisation).
	*/
	function initLangCode()
	{
		return $this->other->initLangCode();
	}
	
	/**
	 * Get tree table name
	 * @return string tree table name
	 */
	public function getTreeTable()
	{
		return $this->other->getTreeTable();
	}
	
	/**
	 * Get object data table
	 * @return type
	 */
	public function getObjectDataTable()
	{
		return $this->other->getObjectDataTable();
	}
	
	/**
	 * Get tree primary key
	 * @return string column of pk
	 */
	public function getTreePk()
	{
		return $this->other->getTreePk();
	}
	
	/**
	 * Get reference table if available
	 */
	public function getTableReference()
	{
		return $this->other->getTableReference();
	}
	
	/**
	 * Get default gap	 * @return int
	 */
	public function getGap()
	{
		return $this->other->getGap();
	}
	
	/***
	 * reset in tree cache
	 */
	public function resetInTreeCache()
	{
		return $this->other->resetInTreeCache();
	}


	/**
	* set table names
	* The primary key of the table containing your object_data must be 'obj_id'
	* You may use a reference table.
	* If no reference table is specified the given tree table is directly joined
	* with the given object_data table.
	* The primary key in object_data table and its foreign key in reference table must have the same name!
	*
	* @param	string	table name of tree table
	* @param	string	table name of object_data table
	* @param	string	table name of object_reference table (optional)
	* @access	public
	* @return	boolean
	 *
	 * @throws InvalidArgumentException
	*/
	function setTableNames($a_table_tree,$a_table_obj_data,$a_table_obj_reference = "")
	{
		return $this->other->setTableNames($a_table_tree, $a_table_obj_data, $a_table_obj_reference);
	}

	/**
	* set column containing primary key in reference table
	* @access	public
	* @param	string	column name
	* @return	boolean	true, when successfully set
	* @throws InvalidArgumentException
	*/
	function setReferenceTablePK($a_column_name)
	{
		return $this->other->setReferenceTablePK($a_column_name);
	}

	/**
	* set column containing primary key in object table
	* @access	public
	* @param	string	column name
	* @return	boolean	true, when successfully set
	* @throws InvalidArgumentException
	*/
	function setObjectTablePK($a_column_name)
	{
		return $this->other->setObjectTablePK($a_column_name);
	}

	/**
	* set column containing primary key in tree table
	* @access	public
	* @param	string	column name
	* @return	boolean	true, when successfully set
	* @throws InvalidArgumentException
	*/
	function setTreeTablePK($a_column_name)
	{
		return $this->other->setTreeTablePK($a_column_name);
	}

	/**
	* build join depending on table settings
	* @access	private
	* @return	string
	*/
	function buildJoin()
	{
		return $this->other->buildJoin();
	}
	
	/**
	 * Get relation of two nodes
	 * @param int $a_node_a
	 * @param int $a_node_b
	 */
	public function getRelation($a_node_a, $a_node_b)
	{
		return $this->other->getRelation($a_node_a, $a_node_b);
	}
	
	/**
	 * get relation of two nodes by node data
	 * @param array $a_node_a_arr
	 * @param array $a_node_b_arr
	 * 
	 */
	public function getRelationOfNodes($a_node_a_arr, $a_node_b_arr)
	{
		return $this->other->getRelationOfNodes($a_node_a_arr, $a_node_b_arr);
	}
	
	/**
	 * Get node child ids
	 * @global type $ilDB
	 * @param type $a_node
	 * @return type
	 */
	public function getChildIds($a_node)
	{
		return $this->other->getChildIds($a_node);
	}

	/**
	* get child nodes of given node
	* @access	public
	* @param	integer		node_id
	* @param	string		sort order of returned childs, optional (possible values: 'title','desc','last_update' or 'type')
	* @param	string		sort direction, optional (possible values: 'DESC' or 'ASC'; defalut is 'ASC')
	* @return	array		with node data of all childs or empty array
	* @throws InvalidArgumentException
	*/
	function getChilds($a_node_id, $a_order = "", $a_direction = "ASC")
	{
		$key = $this->getCacheKey($a_node_id);
		if (isset($this->cache[$a_node_id])) {
			$data = $this->cache[$a_node_id];
		}
		else if ($this->global_cache->exists($key)) {
			$data = $this->global_cache->get($key);
			// this takes care of an cache quirk, where empty array is null
			if ($data === null) {
				$data = [];
			}
			$this->cache[$a_node_id] = $data;
		}
		else {
			$data = $this->other->getChilds($a_node_id);
			$this->cache[$a_node_id] = $data;
			$this->global_cache->set($key, $data);
		}

		if ($a_order !== "") {
			usort($data, function($l,$r) use ($a_order) {
				$l = $l[$a_order];
				$r = $r[$a_order];
				if ($l == $r) {
					return 0;
				}
				if ($l < $r) {
					return -1;
				}
				return 1;
			});
		}

		if ($a_direction !== "ASC") {
			$data = array_reverse($data);
		}

		return $data;
	}

	/**
	 * @var	array
	 */
	protected $cache;

	protected function getCacheKey($node_id, $tree_id = null) {
		if ($tree_id === null) {
			$tree_id = $this->other->getTreeId();
		}
		return "node_".$tree_id."_".$node_id;
	}

	protected function purgeCache($node_id) {
		$key = $this->getCacheKey($node_id);
		unset($this->cache[$key]);
		$this->global_cache->delete($key);

		$path = $this->getPathFull($node_id);
		foreach ($path as $node_id) {
			$key = $this->getCacheKey($node_id);
			unset($this->cache[$key]);
			$this->global_cache->delete($key);
		}
	}

	/**
	* get child nodes of given node (exclude filtered obj_types)
	* @access	public
	* @param	array		objects to filter (e.g array('rolf'))
	* @param	integer		node_id
	* @param	string		sort order of returned childs, optional (possible values: 'title','desc','last_update' or 'type')
	* @param	string		sort direction, optional (possible values: 'DESC' or 'ASC'; defalut is 'ASC')
	* @return	array		with node data of all childs or empty array
	*/
	function getFilteredChilds($a_filter,$a_node,$a_order = "",$a_direction = "ASC")
	{
		return $this->other->getFilteredChilds($a_filter, $a_node, $a_order, $a_direction);
	}


	/**
	* get child nodes of given node by object type
	* @access	public
	* @param	integer		node_id
	* @param	string		object type
	* @return	array		with node data of all childs or empty array
	* @throws InvalidArgumentException
	*/
	function getChildsByType($a_node_id,$a_type)
	{
		return $this->other->getChildsByType($a_node_id, $a_type);
	}


	/**
	* get child nodes of given node by object type
	* @access	public
	* @param	integer		node_id
	* @param	array		array of object type
	* @return	array		with node data of all childs or empty array
	* @throws InvalidArgumentException
	*/
	public function getChildsByTypeFilter($a_node_id,$a_types,$a_order = "",$a_direction = "ASC")
	{
		return $this->other->getChildsByTypeFilter($a_node_id, $a_types, $a_order, $a_direction);
	}
	
	/**
	 * Insert node from trash deletes trash entry.
	 * If we have database query exceptions we could wrap insertNode in try/catch 
	 * and rollback if the insert failed.
	 * 
	 * @param type $a_source_id
	 * @param type $a_target_id
	 * @param type $a_tree_id
	 *
	 * @throws InvalidArgumentException
	 */
	public function insertNodeFromTrash($a_source_id, $a_target_id, $a_tree_id, $a_pos = IL_LAST_NODE, $a_reset_deleted_date = false)
	{
		$this->purgeCache($a_source_id);
		$this->purgeCache($a_target_id);
		return $this->other->insertNodeFromTrash($a_source_id, $a_target_id, $a_tree_id, $a_pos, $a_reset_deleted_date);
	}
	
	
	/**
	* insert new node with node_id under parent node with parent_id
	* @access	public
	* @param	integer		node_id
	* @param	integer		parent_id
	* @param	integer		IL_LAST_NODE | IL_FIRST_NODE | node id of preceding child
	* @throws InvalidArgumentException
	*/
	public function insertNode($a_node_id, $a_parent_id, $a_pos = IL_LAST_NODE, $a_reset_deletion_date = false)
	{
		$this->purgeCache($a_node_id);
		$this->purgeCache($a_parent_id);
		return $this->other->insertNode($a_node_id, $a_parent_id, $a_pos, $a_reset_deletion_date);
	}
	
	/**
	 * get filtered subtree
	 * 
	 * get all subtree nodes beginning at a specific node
	 * excluding specific object types and their child nodes.
	 * 
	 * E.g getFilteredSubTreeNodes()
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function getFilteredSubTree($a_node_id,$a_filter = array())
	{
		return $this->other->getFilteredSubTree($a_node_id, $a_filter);
	}
	
	/**
	 * Get all ids of subnodes
	 * @return 
	 * @param object $a_ref_id
	 */
	public function getSubTreeIds($a_ref_id)
	{
		return $this->other->getSubTreeIds($a_ref_id);
	}
	

	/**
	* get all nodes in the subtree under specified node
	*
	* @access	public
	* @param	array		node_data
	* @param    boolean     with data: default is true otherwise this function return only a ref_id array
	* @return	array		2-dim (int/array) key, node_data of each subtree node including the specified node
	* @throws InvalidArgumentException
	*/
	function getSubTree($a_node,$a_with_data = true, $a_type = "")
	{
		return $this->other->getSubTree($a_node, $a_with_data, $a_type);
	}

	/**
	* get types of nodes in the subtree under specified node
	*
	* @access	public
	* @param	array		node_id
	* @param	array		object types to filter e.g array('rolf')
	* @return	array		2-dim (int/array) key, node_data of each subtree node including the specified node
	*/
	function getSubTreeTypes($a_node,$a_filter = 0)
	{
		return $this->other->getSubTreeTypes($a_node, $a_filter);
	}

	/**
	 * delete node and the whole subtree under this node
	 * @access	public
	 * @param	array		node_data of a node
	 * @throws InvalidArgumentException
	 * @throws ilInvalidTreeStructureException
	 */
	function deleteTree($a_node)
	{
		$this->purgeCache($a_node);
		return $this->other->deleteTree($a_node);
	}
	
	/**
	 * Validate parent relations of tree
	 * @return int[] array of failure nodes
	 */
	public function validateParentRelations()
	{
		return $this->other->validateParentRelations();
	}

	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode.
	* This function chooses the algorithm to be used.
	*
	* @access	public
	* @param	integer	node_id of endnode
	* @param	integer	node_id of startnode (optional)
	* @return	array	ordered path info (id,title,parent) from start to end
	*/
	function getPathFull($a_endnode_id, $a_startnode_id = 0)
	{
		return $this->other->getPathFull($a_endnode_id, $a_startnode_id);
	}
	

	/**
	 * Preload depth/parent
	 *
	 * @param
	 * @return
	 */
	function preloadDepthParent($a_node_ids)
	{
		return $this->other->preloadDepthParent($a_node_ids);
	}

	/**
	* get path from a given startnode to a given endnode
	* if startnode is not given the rootnode is startnode
	* @access	public
	* @param	integer		node_id of endnode
	* @param	integer		node_id of startnode (optional)
	* @return	array		all path ids from startnode to endnode
	* @throws InvalidArgumentException
	*/
	public function getPathId($a_endnode_id, $a_startnode_id = 0)
	{
		return $this->other->getPathId($a_endnode_id, $a_startnode_id);
	}

	// BEGIN WebDAV: getNodePathForTitlePath function added
	/**
	* Converts a path consisting of object titles into a path consisting of tree
	* nodes. The comparison is non-case sensitive.
	*
	* Note: this function returns the same result as getNodePath, 
	* but takes a title path as parameter.
	*
	* @access	public
	* @param	Array	Path array with object titles.
	*                       e.g. array('ILIAS','English','Course A')
	* @param	ref_id	Startnode of the relative path. 
	*                       Specify null, if the title path is an absolute path.
	*                       Specify a ref id, if the title path is a relative 
	*                       path starting at this ref id.
	* @return	array	ordered path info (depth,parent,child,obj_id,type,title)
	*               or null, if the title path can not be converted into a node path.
	*/
	function getNodePathForTitlePath($titlePath, $a_startnode_id = null)
	{
		return $this->other->getNodePathForTitlePath($titlePath, $a_startnode_id);
	}
	// END WebDAV: getNodePathForTitlePath function added
	// END WebDAV: getNodePath function added
	/**
	* Returns the node path for the specified object reference.
	*
	* Note: this function returns the same result as getNodePathForTitlePath, 
	* but takes ref-id's as parameters.
	*
	* This function differs from getPathFull, in the following aspects:
	* - The title of an object is not translated into the language of the user
	* - This function is significantly faster than getPathFull.
	*
	* @access	public
	* @param	integer	node_id of endnode
	* @param	integer	node_id of startnode (optional)
	* @return	array	ordered path info (depth,parent,child,obj_id,type,title)
	*               or null, if the node_id can not be converted into a node path.
	*/
	function getNodePath($a_endnode_id, $a_startnode_id = 0)
	{
		return $this->other->getNodePath($a_endnode_id, $a_startnode_id);
	}
	// END WebDAV: getNodePath function added

	/**
	* check consistence of tree
	* all left & right values are checked if they are exists only once
	* @access	public
	* @return	boolean		true if tree is ok; otherwise throws error object
	* @throws ilInvalidTreeStructureException
	*/
	function checkTree()
	{
		return $this->other->checkTree();
	}

	/**
	 * check, if all childs of tree nodes exist in object table
	 *
	 * @param bool $a_no_zero_child
	 * @return bool
	 * @throws ilInvalidTreeStructureException
	*/
	function checkTreeChilds($a_no_zero_child = true)
	{
		return $this->other->checkTreeChilds($a_no_zero_child);
	}

	/**
	 * Return the current maximum depth in the tree
	 * @access	public
	 * @return	integer	max depth level of tree
	 */
	public function getMaximumDepth()
	{
		return $this->other->getMaximumDepth();
	}

	/**
	* return depth of a node in tree
	* @access	private
	* @param	integer		node_id of parent's node_id
	* @return	integer		depth of node in tree
	*/
	function getDepth($a_node_id)
	{
		return $this->other->getDepth($a_node_id);
	}
	
	/**
	 * return all columns of tabel tree
	 * @param type $a_node_id
	 * @return array of table column => values
	 * 
	 * @throws InvalidArgumentException
	 */
	public function getNodeTreeData($a_node_id)
	{
		return $this->other->getNodeTreeData($a_node_id);
	}


	/**
	* get all information of a node.
	* get data of a specific node from tree and object_data
	* @access	public
	* @param	integer		node id
	* @return	array		2-dim (int/str) node_data
	* @throws InvalidArgumentException
	*/
	// BEGIN WebDAV: Pass tree id to this method
	//function getNodeData($a_node_id)
	function getNodeData($a_node_id, $a_tree_pk = null)
	// END PATCH WebDAV: Pass tree id to this method
	{
		return $this->other->getNodeData($a_node_id, $a_tree_pk);
	}
	
	/**
	* get data of parent node from tree and object_data
	* @access	private
 	* @param	object	db	db result object containing node_data
	* @return	array		2-dim (int/str) node_data
	* TODO: select description twice for compability. Please use 'desc' in future only
	*/
	function fetchNodeData($a_row)
	{
		return $this->other->fetchNodeData($a_row);
	}

	/**
	 * Get translation data from object cache (trigger in object cache on preload)
	 *
	 * @param	array	$a_obj_ids		object ids
	 */
	protected function fetchTranslationFromObjectDataCache($a_obj_ids)
	{
		return $this->other->fetchTranslationFromObjectDataCache($a_obj_ids);
	}


	/**
	* get all information of a node.
	* get data of a specific node from tree and object_data
	* @access	public
	* @param	integer		node id
	* @return	boolean		true, if node id is in tree
	*/
	function isInTree($a_node_id)
	{
		return $this->other->isInTree($a_node_id);
	}

	/**
	* get data of parent node from tree and object_data
	* @access	public
 	* @param	integer		node id
	* @return	array
	* @throws InvalidArgumentException
	*/
	public function getParentNodeData($a_node_id)
	{
		return $this->other->getParentNodeData($a_node_id);
	}

	/**
	* checks if a node is in the path of an other node
	* @access	public
 	* @param	integer		object id of start node
	* @param    integer     object id of query node
	* @return	integer		number of entries
	*/
	public function isGrandChild($a_startnode_id,$a_querynode_id)
	{
		return $this->other->isGrandChild($a_startnode_id, $a_querynode_id);
	}

	/**
	* create a new tree
	* to do: ???
	* @param	integer		a_tree_id: obj_id of object where tree belongs to
	* @param	integer		a_node_id: root node of tree (optional; default is tree_id itself)
	* @return	boolean		true on success
	* @throws InvalidArgumentException
	* @access	public
	*/
	function addTree($a_tree_id,$a_node_id = -1)
	{
		return $this->other->addTree($a_tree_id, $a_node_id);
	}

	/**
	 * get nodes by type
	 * @param	integer		a_tree_id: obj_id of object where tree belongs to
	 * @param	integer		a_type_id: type of object
	 * @access	public
	 * @throws InvalidArgumentException
	 * @return array
	 * @deprecated since 4.4.0
	 */
	public function getNodeDataByType($a_type)
	{
		return $this->other->getNodeDataByType($a_type);
	}

	/**
	* remove an existing tree
	*
	* @param	integer		a_tree_id: tree to be removed
	* @return	boolean		true on success
	* @access	public
	* @throws InvalidArgumentException
 	*/
	public function removeTree($a_tree_id)
	{
		return $this->other->removeTree($a_tree_id);
	}
	
	/**
	 * Wrapper for saveSubTree
	 * @param int $a_node_id
	 * @param bool $a_set_deleted
	 * @return integer
	 * @throws InvalidArgumentException
	 */
	public function moveToTrash($a_node_id, $a_set_deleted = false)
	{
		$this->purgeCache($a_node_id);
		return $this->other->moveToTrash($a_node_id, $a_set_deleted);
	}

	/**
	 * Use the wrapper moveToTrash
	 * save subtree: delete a subtree (defined by node_id) to a new tree
	 * with $this->other->tree_id -node_id. This is neccessary for undelete functionality
	 * @param	integer	node_id
	 * @return	integer
	 * @access	public
	 * @throws InvalidArgumentException
	 * @deprecated since 4.4.0
	 */
	public function saveSubTree($a_node_id, $a_set_deleted = false)
	{
		$this->purgeCache($a_node_id);
		return $this->other->saveSubTree($a_node_id, $a_set_deleted);
	}

	/**
	 * This is a wrapper for isSaved() with a more useful name
	 * @param int $a_node_id
	 */
	public function isDeleted($a_node_id)
	{
		return $this->other->isDeleted($a_node_id);
	}

	/**
	 * Use method isDeleted
	 * check if node is saved
	 * @deprecated since 4.4.0
	 */
	public function isSaved($a_node_id)
	{
		return $this->other->isSaved($a_node_id);
	}

	/**
	 * Preload deleted information
	 *
	 * @param array nodfe ids
	 * @return bool
	 */
	public function preloadDeleted($a_node_ids)
	{
		return $this->other->preloadDeleted($a_node_ids);
	}


	/**
	* get data saved/deleted nodes
	* @return	array	data
	* @param	integer	id of parent object of saved object
	* @access	public
	* @throws InvalidArgumentException
	*/
	function getSavedNodeData($a_parent_id)
	{
		return $this->other->getSavedNodeData($a_parent_id);
	}
	
	/**
	* get object id of saved/deleted nodes
	* @return	array	data
	* @param	array	object ids to check
	* @access	public
	*/
	function getSavedNodeObjIds(array $a_obj_ids)
	{
		return $this->other->getSavedNodeObjIds($a_obj_ids);
	}

	/**
	* get parent id of given node
	* @access	public
	* @param	integer	node id
	* @return	integer	parent id
	* @throws InvalidArgumentException
	*/
	function getParentId($a_node_id)
	{
		return $this->other->getParentId($a_node_id);
	}

	/**
	* get left value of given node
	* @access	public
	* @param	integer	node id
	* @return	integer	left value
	* @throws InvalidArgumentException
	*/
	function getLeftValue($a_node_id)
	{
		return $this->other->getLeftValue($a_node_id);
	}

	/**
	* get sequence number of node in sibling sequence
	* @access	public
	* @param	array		node
	* @return	integer		sequence number
	* @throws InvalidArgumentException
	*/
	function getChildSequenceNumber($a_node, $type = "")
	{
		return $this->other->getChildSequenceNumber($a_node, $type);
	}

	/**
	* read root id from database
	* @param root_id
	* @access public
	* @return int new root id
	*/
	function readRootId()
	{
		return $this->other->readRootId();
	}

	/**
	* get the root id of tree
	* @access	public
	* @return	integer	root node id
	*/
	function getRootId()
	{
		return $this->other->getRootId();
	}

	function setRootId($a_root_id)
	{
		return $this->other->setRootId($a_root_id);
	}

	/**
	* get tree id
	* @access	public
	* @return	integer	tree id
	*/
	function getTreeId()
	{
		return $this->other->getTreeId();
	}

	/**
	* set tree id
	* @access	public
	* @return	integer	tree id
	*/
	function setTreeId($a_tree_id)
	{
		return $this->other->setTreeId($a_tree_id);
	}

	/**
	* get node data of successor node
	*
	* @access	public
	* @param	integer		node id
	* @return	array		node data array
	* @throws InvalidArgumentException
	*/
	function fetchSuccessorNode($a_node_id, $a_type = "")
	{
		return $this->other->fetchSuccessorNode($a_node_id, $a_type);
	}

	/**
	* get node data of predecessor node
	*
	* @access	public
	* @param	integer		node id
	* @return	array		node data array
	* @throws InvalidArgumentException
	*/
	function fetchPredecessorNode($a_node_id, $a_type = "")
	{
		return $this->other->fetchPredecessorNode($a_node_id, $a_type);
	}

	/**
	* Wrapper for renumber. This method locks the table tree
	* (recursive)
	* @access	public
	* @param	integer	node_id where to start (usually the root node)
	* @param	integer	first left value of start node (usually 1)
	* @return	integer	current left value of recursive call
	*/
	function renumber($node_id = 1, $i = 1)
	{
		$this->purgeCache($node_id);
		return $this->other->renumber($node_id, $i);
	}

	// PRIVATE
	/**
	* This method is private. Always call ilTree->renumber() since it locks the tree table
 	* renumber left/right values and close the gaps in numbers
	* (recursive)
	* @access	private
	* @param	integer	node_id where to start (usually the root node)
	* @param	integer	first left value of start node (usually 1)
	* @return	integer	current left value of recursive call
	*/
	function __renumber($node_id = 1, $i = 1)
	{
		$this->purgeCache($node_id);
		return $this->other->__renumber($node_id, $i);
	}


	/**
	* Check for parent type
	* e.g check if a folder (ref_id 3) is in a parent course obj => checkForParentType(3,'crs');
	*
 	* @access	public
	* @param	integer	ref_id
	* @param	string type
	* @return	mixed false if item is not in tree, 
	* 				  int (object ref_id) > 0 if path container course, int 0 if pathc does not contain the object type 
	*/
	function checkForParentType($a_ref_id,$a_type,$a_exclude_source_check = false)
	{
		return $this->other->checkForParentType($a_ref_id, $a_type, $a_exclude_source_check);
	}

	/**
	* Check if operations are done on main tree
	*
 	* @access	private
	* @return boolean
	*/
	public function __isMainTree()
	{
		return $this->other->__isMainTree();
	}

	/**
	 * Check for deleteTree()
	 * compares a subtree of a given node by checking lft, rgt against parent relation
	 *
 	 * @access	private
	 * @param array node data from ilTree::getNodeData()
	 * @return boolean
	 *
	 * @throws ilInvalidTreeStructureException
	 * @deprecated since 4.4.0
	*/
	function __checkDelete($a_node)
	{
		return $this->other->__checkDelete($a_node);
	}

	/**
	 * 
	 * @global type $ilDB
	 * @param type $a_node_id
	 * @param type $parent_childs
	 * @return boolean
	 * @throws ilInvalidTreeStructureException
	 * @deprecated since 4.4.0
	 */
	function __getSubTreeByParentRelation($a_node_id,&$parent_childs)
	{
		return $this->other->__getSubTreeByParentRelation($a_node_id, $parent_childs);
	}

	/**
	 * @param $lft_childs
	 * @param $parent_childs
	 * @return bool
	 * @throws ilInvalidTreeStructureException
	 * @deprecated since 4.4.0
	 */
	function __validateSubtrees(&$lft_childs,$parent_childs)
	{
		return $this->other->__validateSubtrees($lft_childs, $parent_childs);
	}
	
	/**
	 * Move Tree Implementation
	 * 
	 * @access	public
	 * @param int source ref_id
	 * @param int target ref_id
	 * @param int location IL_LAST_NODE or IL_FIRST_NODE (IL_FIRST_NODE not implemented yet)
	 * @return bool
	 */
	public function moveTree($a_source_id, $a_target_id, $a_location = self::POS_LAST_NODE)
	{
		$this->purgeCache($a_source_id);
		$this->purgeCache($a_target_id);
		return $this->other->moveTree($a_source_id, $a_target_id, $a_location);
	}
	
	
	
	
	/**
	 * This method is used for change existing objects 
	 * and returns all necessary information for this action.
	 * The former use of ilTree::getSubtree needs to much memory.
	 * @param ref_id ref_id of source node 
	 * @return 
	 */
	public function getRbacSubtreeInfo($a_endnode_id)
	{
		return $this->other->getRbacSubtreeInfo($a_endnode_id);
	}
	

	/**
	 * Get tree subtree query
	 * @param type $a_node_id
	 * @param type $a_types
	 * @param type $a_force_join_reference
	 * @return type
	 */
	public function getSubTreeQuery($a_node_id,$a_fields = array(), $a_types = '', $a_force_join_reference = false)
	{
		return $this->other->getSubTreeQuery($a_node_id, $a_fields, $a_types, $a_force_join_reference);
	}
	
	
	/**
	 * get all node ids in the subtree under specified node id, filter by object ids
	 *
	 * @param int $a_node_id
	 * @param array $a_obj_ids
	 * @param array $a_fields
	 * @return	array	
	 */
	public function getSubTreeFilteredByObjIds($a_node_id, array $a_obj_ids, array $a_fields = array())
	{
		return $this->other->getSubTreeFilteredByObjIds($a_node_id, $a_obj_ids, $a_fields);
	}
	
	public function deleteNode($a_tree_id,$a_node_id)
	{
		$this->purgeCache($a_node_id, $a_tree_id);
		return $this->other->deleteNode($a_tree_id, $a_node_id);
	}

	/**
	 * Lookup object types in trash
	 * @global type $ilDB
	 * @return type
	 */
	public function lookupTrashedObjectTypes()
	{
		return $this->other->lookupTrashedObjectTypes();
	}
} // END class.tree

