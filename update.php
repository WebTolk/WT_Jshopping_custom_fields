<?php
/**
* @version 1.0.0
* @author Sergey tolkachyov
* @package wt_jshopping_custom_fields for Jshopping
* @copyright Copyright (C) 2022 https://web-tolk.ru
* @license GNU/GPL
**/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Version;
use Joomla\CMS\Factory;

$name = 'WT JShopping Custom fields';
$element = 'wt_jshopping_custom_fields';
$version = '1.0.0';
$cache = '{"creationDate":"19.12.2022","author":"Sergey Tolkachyov","authorEmail":"info@web-tolk.ru","authorUrl":"https://web-tolk.ru","version":"' . $version.'"}';
$params = '{}';

if ((new Version())->isCompatible('4.0'))
		{
			$db         = Factory::getContainer()->get('DatabaseDriver');
		} else {
			$db         = Factory::getDbo();
		}

$query = "
	create table if not exists #__jshopping_custom_fields_values
		(
			product_id  int        null comment 'JoomShopping product id',
			category_id int        null comment 'JoomShopping category id',
			field_value mediumtext not null comment 'JoomShopping custom field JForm value',
			constraint product_id
				unique (product_id),
				unique (category_id)
		);
";
$db->setQuery($query);
$db->execute();
if ((new Version())->isCompatible('4.0'))
{
	$addon = \JSFactory::getTable('addon');
} else {
	$addon = JSFactory::getTable('addon');
}
$addon->loadAlias($element);
$addon->set('name', $name);
$addon->set('version', $version);
//$addon->set('uninstall', '/components/com_jshopping/addons/' . $element . '/uninstall.php');
$addon->store();
?>