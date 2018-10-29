<?php

declare(strict_types = 1);

Interface HistorizedRepositoryPlugin
{
	public function getObjType() : string;
	public function getEmptyPayload() : array;
	public function getTree() : \ilTree;
	public function extractPayloadByPluginObject(\ilObjectPlugin $obj) : array;
}