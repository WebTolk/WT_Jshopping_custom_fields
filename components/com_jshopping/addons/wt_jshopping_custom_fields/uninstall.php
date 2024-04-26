<?php
/**
* @version 0.0.7
* @author А.П.В.
* @package ba_custom_fields for Jshopping
* @copyright Copyright (C) 2010 blog-about.ru. All rights reserved.
* @license GNU/GPL
**/
defined('_JEXEC') or die('Restricted access');

$db = JFactory::getDbo();

$type = 'plugin';
$element = 'ba_custom_fields';
$folders = array('jshoppingproducts', 'jshoppingadmin');

foreach($folders as $folder){
	$db->setQuery("
		DELETE FROM `#__extensions`
		WHERE `element` = '" . $element . "' AND `folder` = '" . $folder . "' AND `type` = '" . $type . "'");
	$db->query();
}

$db->setQuery("
	DROP TABLE `#__jshopping_custom_fields`
");
$db->query();

$db->setQuery("
	DROP TABLE `#__jshopping_custom_fields_values`
");
$db->query();

jimport('joomla.filesystem.folder');
JFolder::delete(JPATH_ROOT . '/components/com_jshopping/addons/ba_custom_fields/');
JFolder::delete(JPATH_ROOT . '/components/com_jshopping/lang/ba_custom_fields/');
JFolder::delete(JPATH_ROOT . '/plugins/jshoppingadmin/ba_custom_fields/');
JFolder::delete(JPATH_ROOT . '/plugins/jshoppingproducts/ba_custom_fields/');
?>