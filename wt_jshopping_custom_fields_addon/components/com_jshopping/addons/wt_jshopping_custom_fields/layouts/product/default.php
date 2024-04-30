<?php
defined('_JEXEC') or die('Restricted access');
/**
 * @var $displayData array
 */

if($displayData['label']){
	echo $displayData['label'].' ';
}
echo $displayData['field_value'];