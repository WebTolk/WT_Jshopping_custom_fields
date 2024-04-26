<?php
defined('_JEXEC') or die('Restricted access');
/**
 * @var $displayData array
 */

if($displayData['label']){
	echo '<h4 class="mt-4">'.$displayData['label'].'</h4>';
}
echo $displayData['field_value'];