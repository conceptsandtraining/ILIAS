<?php

chdir(__DIR__."/..");

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

$global_cache = ilGlobalCache::getInstance(ilGlobalCache::COMP_TREE);

global $DIC;

$tree = new ilTree(ROOT_FOLDER_ID);
$cached_tree = new ilCachedTree(
	$tree,
	$global_cache,
	$DIC["ilObjDataCache"],
	$DIC["objDefinition"]
);

$methods = [
	"getChilds" => function($tree, $node) {
		return $tree->getChilds($node);
	},
	/*"getChilds_withOrderAndDirection" => function($tree, $node) {
		return $tree->getChilds($node, "title", "ASC");
	},*/
	"getChildsByType" => function($tree, $node) {
		return $tree->getChildsByType($node, "fold");
	},
	"getChildsByTypeFilter" => function($tree, $node) {
		return $tree->getChildsByTypeFilter($node, ["crs", "role"]);
	},
	/*"getChildsByTypeFilter_withOrderAndDirection" => function($tree, $node) {
		return $tree->getChildsByTypeFilter($node, ["crs", "role"], "description", "DESC");
	},*/
	"getSubTreeIds" => function($tree, $node) {
		$ids = $tree->getSubTreeIds($node);
		sort($ids);
		return $ids;
	},
	"getSubTree" => function($tree, $node) {
		$node = $tree->getNodeData($node);
		$sub = $tree->getSubTree($node);
		usort($sub, function($l, $r) { return $l <=> $r; });
		return $sub;
	},
	"getSubTree_noData" => function($tree, $node) {
		$node = $tree->getNodeData($node);
		$ids = $tree->getSubTree($node, false);
		sort($ids);
		return $ids;
	},
	"getSubTree_filtered" => function($tree, $node) {
		$node = $tree->getNodeData($node);
		$sub = $tree->getSubTree($node, true, ["crs", "role"]);
		usort($sub, function($l, $r) { return $l <=> $r; });
		return $sub;
	},
	"getSubTree_filtered2" => function($tree, $node) {
		$node = $tree->getNodeData($node);
		$sub = $tree->getSubTree($node, true, "fold");
		usort($sub, function($l, $r) { return $l <=> $r; });
		return $sub;
	},
	"getSubTree_filtered3" => function($tree, $node) {
		$node = $tree->getNodeData($node);
		$sub = $tree->getSubTree($node, false, "fold");
		usort($sub, function($l, $r) { return $l <=> $r; });
		return $sub;
	},
	"getSubTreeTypes" => function($tree, $node) {
		$types = $tree->getSubTreeTypes($node);
		sort($types);
		return $types;
	},
	"getSubTreeTypes_withFilter" => function($tree, $node) {
		$types = $tree->getSubTreeTypes($node, ["crs", "cat"]);
		sort($types);
		return $types;
	},
	"getPathFull" => function($tree, $node) {
		return $tree->getPathFull($node);
	},
	"getPathFull_withStartnode" => function($tree, $node) {
		return $tree->getPathFull($node, 1);
	},
	"getPathId" => function($tree, $node) {
		return $tree->getPathId($node);
	},
	"getPathId_withStartnode" => function($tree, $node) {
		return $tree->getPathId($node, 1);
	},
	"getNodeData" => function($tree, $node) {
		return $tree->getNodeData($node);
	},
	"isInTree" => function($tree, $node) {
		return $tree->isInTree($node);
	},
	"getParentNodeData" => function($tree, $node) {
		return $tree->getParentNodeData($node);
	},
	"isGrandChild" => function($tree, $node) {
		return $tree->isGrandChild(1, $node);
	},
	"getParentId" => function($tree, $node) {
		return $tree->getParentId($node);
	},
	"checkForParentType" => function($tree, $node) {
		return $tree->checkForParentType($node, "crs");
	},
	"checkForParentType_withSourceCheck" => function($tree, $node) {
		return $tree->checkForParentType($node, "crs", true);
	}
];

$nodes = [];

$db = $DIC->database();
$res = $db->query("SELECT child FROM tree WHERE tree = 1");
while($row = $db->fetchAssoc($res)) {
	$nodes[] = $row["child"];
}


$had_problems = false;

foreach ($nodes as $node) {
	echo "<pre>Checking: $node\n</pre>\n";
	$problems = [];
	foreach ($methods as $name => $method) {
		$original = $method($tree, $node);
		$cached = $method($cached_tree, $node);
		if ($original != $cached) {
			$problems[$name] = ["original" => $original, "cached" => $cached];
		}
	}

	if ($problems) {
		$had_problems = true;

		echo "<pre>\n";
		echo "==============================================================\n";
		echo "\n";
		echo "PROBLEMS WITH NODE $node:\n";
		echo "\n";
		echo "==============================================================\n";

		foreach ($problems as $name => $problem) {
			echo "\n\nMETHOD $name\n\n";
			echo "ORIGINAL:\n";
			print_r($problem["original"]);
			echo "\n";
			echo "CACHED:\n";
			print_r($problem["cached"]);
			echo "\n";
		}
		echo "</pre>\n";
	}
};

if ($had_problems) {
	echo "<pre>FAILED</pre>";
	die(1);
}
else {
	echo "<pre>DONE</pre>";
	die(0);
}
