<?php
defined("_JEXEC") or die("Restricted access");

jimport("joomla.filesystem.folder");
JFolder::delete(JPATH_ROOT . '/components/com_jshopping/addons/wt_jshopping_custom_fields');
?>