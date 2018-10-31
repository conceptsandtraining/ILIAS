<?php

declare(strict_types = 1);

interface HistorizedRepositoryPlugin
{
	/**
	 * Object type to be handled by this plugin, e.g. xetr
	 */
	public function getObjType() : string;
	/**
	 * Payload corrsponding to no object
	 */
	public function getEmptyPayload() : array;
	/**
	 * Get instance of ilTree
	 */
	public function getTree() : \ilTree;
	/**
	 * Extract data from object, seee self::getObjectType
	 */
	public function extractPayloadByPluginObject(\ilObjectPlugin $obj) : array;
	/**
	 * Get a list of relevant hist cases, e.g. crs, usr_crs, wbd_crs ...
	 */
	public function relevantHistCases() : array;
}