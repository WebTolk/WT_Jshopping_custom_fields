<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;

JSFactory::loadExtLanguageFile('wt_jshopping_custom_fields');
$form   = Form::getInstance("params", __DIR__ . "/fields.xml", array("control" => "params"));
$params = (object) $this->params;
$form->bind($params);
$wa  = Factory::getDocument()->getWebAssetManager()
	->addInlineStyle('#adminForm > .control-group {display:flex;flex-direction:column;}')
	->usePreset('choicesjs')
	->useScript('webcomponent.field-fancy-select');
$doc = Factory::getApplication()->getDocument();
// Remove JShopping css here
foreach ($doc->_styleSheets as $key => $value)
{
	if (strpos($key, 'com_jshopping/css/style.css') == true)
	{
		unset($doc->_styleSheets[$key]);
	}

}

echo HTMLHelper::_('uitab.startTabSet', 'wt_jshopping_custom_fields', ['active' => 'products', 'recall' => true, 'breakpoint' => 768]);
	echo HTMLHelper::_('uitab.addTab', 'products', 'products', 'Товары');
		echo $form->renderField("wt_jshopping_custom_fields_products");
	echo HTMLHelper::_('uitab.endTab');

	echo HTMLHelper::_('uitab.addTab', 'category', 'category', 'Категории');
		echo $form->renderField("wt_jshopping_custom_fields_categories");
	echo HTMLHelper::_('uitab.endTab');
echo HTMLHelper::_('uitab.endTabSet');


