<?php

namespace Joomla\Plugin\Jshoppingadmin\Wt_jshopping_custom_fields\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use SimpleXMLElement;

defined('_JEXEC') or die('Restricted access');

class Wt_jshopping_custom_fields extends CMSPlugin implements SubscriberInterface
{
    protected $allowLegacyListeners = false;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onDisplayProductEditTabsEndTab' => 'onDisplayProductEditTabsEndTab',
            'onDisplayProductEditTabsEnd' => 'onDisplayProductEditTabsEnd',
            'onAfterSaveProduct' => 'onAfterSaveProduct',
            'onBeforeDisplayEditProduct' => 'onBeforeDisplayEditProduct',
            'onAfterRemoveProduct' => 'onAfterRemoveProduct',
            'onBeforeEditCategories' => 'onBeforeEditCategories',
            'onAfterSaveCategory' => 'onAfterSaveCategory',
            'onAfterRemoveCategory' => 'onAfterRemoveCategory'
        ];
    }

	public function onDisplayProductEditTabsEndTab(Event $event): void
	{
        $row = $event->getArgument(0);
        $lists = $event->getArgument(1);
        $tax_value = $event->getArgument(2);

		$addon = \JSFactory::getTable('addon');
		$addon->loadAlias('wt_jshopping_custom_fields');
		$addon_params = (object) $addon->getParams();
		if (empty($addon_params->wt_jshopping_custom_fields_products))
		{
			return;
		}
		echo '<li class="nav-item"><a href="#product_wt_custom_fields" data-toggle="tab" class="nav-link">Custom Fields</a></li>';
	}

	public function onDisplayProductEditTabsEnd(Event $event): void
	{
        $pane = $event->getArgument(0);
        $row = $event->getArgument(1);
        $lists = $event->getArgument(2);
        $tax_value = $event->getArgument(3);
        $currency = $event->getArgument(4);

		$model_langs = \JSFactory::getModel("languages");
		$languages   = $model_langs->getAllLanguages(1);
		$addon       = \JSFactory::getTable('addon');
		$addon->loadAlias('wt_jshopping_custom_fields');
		$addon_params = (object) $addon->getParams();
		if (empty($addon_params->wt_jshopping_custom_fields_products))
		{
			return;
		}

		echo '<div id="product_wt_custom_fields" class="tab-pane">';
		echo '<div class="main-card">';
		echo '<div class="p-3 mb-3 shadow-sm">
			<h4>Custom fields</h4>
			<p>Вы можете создать необходимые Вам поля в настройках аддона в Опции - Дополнения - wt_jshopping_custom_fields</p>
		</div>';
		echo HTMLHelper::_('uitab.startTabSet', 'wt_custom_fields_Tab', array('active' => $languages->lang[0]->lang));
		// Мультиязычные поля
		foreach ($languages as $language)
		{
			$form_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><form></form>');
			$fieldset  = $form_data->addChild('fieldset');
			$fieldset->addAttribute('name', 'wt_jshopping_custom_fields_' . $language->lang);
			echo HTMLHelper::_('uitab.addTab', 'wt_custom_fields_Tab', 'wt_custom_fields_Tab_' . $language->lang, 'Custom fields (' . $language->lang . ')');
			foreach ($addon_params->wt_jshopping_custom_fields_products as $custom_field)
			{

				$custom_field = (object) $custom_field;
				$field        = $fieldset->addChild('field');
				$field->addAttribute('type', $custom_field->field_type);
				$field->addAttribute('label', $custom_field->field_admin_label . ' (' . $language->lang . ')');
				$field->addAttribute('name', OutputFilter::stringURLSafe($custom_field->field_admin_label . '_' . $language->lang));
				$field->addAttribute('class', 'form-control');
				if ($custom_field->field_type == 'editor')
				{
					$field->addAttribute('filter', 'JComponentHelper::filterText');
				}
				elseif ($custom_field->field_type == 'text' || $custom_field->field_type == 'textarea')
				{
					$field->addAttribute('filter', 'safehtml');
				}
				if (!empty($custom_field->field_note))
				{
					$field->addAttribute('description', Text::_($custom_field->field_note));
				}


			}
			$xml  = $form_data->asXML();
			$form = Form::getInstance('wt_jshopping_custom_fields_' . $language->lang, $xml, array("control" => "wt_jshopping_custom_fields[" . $language->lang . "]"));
			if (isset($row->wt_jshopping_custom_fields->{$language->lang}))
			{
				$form->bind($row->wt_jshopping_custom_fields->{$language->lang});
			}
			echo $form->renderFieldset('wt_jshopping_custom_fields_' . $language->lang);

			echo HTMLHelper::_('uitab.endTab');
		}

		echo HTMLHelper::_('uitab.endTabSet');


		echo '</div></div>';
	}

	public function onAfterSaveProduct(Event $event): void
	{
        $product = $event->getArgument(0);

//		$post2                       = Factory::getApplication()->getInput();
		$wt_jshopping_custom_fields = $this->getApplication()->getInput()->post->get('wt_jshopping_custom_fields','','raw');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if (!$wt_jshopping_custom_fields || count((array) $wt_jshopping_custom_fields) == 0)
		{
			$wt_jshopping_custom_fields = json_encode(array());
		}
		else
		{
			$wt_jshopping_custom_fields = json_encode($wt_jshopping_custom_fields);
		}
		$query = "REPLACE INTO `#__jshopping_custom_fields_values` SET `product_id`='" . $db->escape($product->product_id) . "', `field_value`='" . $db->escape($wt_jshopping_custom_fields) . "'";
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * @param $event Event
	 *
	 * @since 1.0.0
	 */
	public function onBeforeDisplayEditProduct(Event $event): void
	{
        $product = $event->getArgument(0);
        $related_products = $event->getArgument(1);
        $lists = $event->getArgument(2);
        $listfreeattributes = $event->getArgument(3);
        $tax_value = $event->getArgument(4);

		$product_id = $this->getApplication()->getInput()->getInt('product_id');
		$db         = Factory::getContainer()->get(DatabaseInterface::class);
		$query      = $db->getQuery(true);
		$query->select('field_value')
			->from('#__jshopping_custom_fields_values')
			->where($db->quoteName('product_id') . ' = ' . $db->quote($product_id));
		$db->setQuery($query);
		$result = $db->loadAssoc();

		if (!empty($result['field_value']))
		{
			$custom_related_products_json = json_decode($result['field_value']);

		}
		else
		{
			$custom_related_products_json = [];
		}
		$product->wt_jshopping_custom_fields = $custom_related_products_json;

	}


	public function onAfterRemoveProduct(Event $event): void
	{
        $ids = $event->getArgument(0);

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query      = $db->getQuery(true);
		$conditions = array(
			$db->quoteName('product_id') . ' IN (' . implode(',', $ids) . ')'
		);
		$query->delete($db->quoteName('#__jshopping_custom_fields_values'))
			->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
	}


	/**
	 * Показываем поля в табе при редактировании категории. \
	 * Получаем и биндуем данные в форму.
	 * @param $event Event
	 *
	 * @since 1.0.0
	 */
	public function onBeforeEditCategories(Event $event): void
    {
        /** @var $view  */
        $view = $event->getArgument(0);

		$category_id = $this->getApplication()->getInput()->getInt('category_id');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query      = $db->getQuery(true);
		$query->select('field_value')
			->from('#__jshopping_custom_fields_values')
			->where($db->quoteName('category_id') . ' = ' . $db->quote($category_id));
		$db->setQuery($query);
		$result = $db->loadAssoc();

		if (!empty($result['field_value']))
		{
			$custom_related_products_json = json_decode($result['field_value']);

		}
		else
		{
			$custom_related_products_json = [];
		}

		$model_langs = \JSFactory::getModel("languages");
		$languages   = $model_langs->getAllLanguages(1);
		$addon       = \JSFactory::getTable('addon');
		$addon->loadAlias('wt_jshopping_custom_fields');
		$addon_params = (object) $addon->getParams();
		if (empty($addon_params->wt_jshopping_custom_fields_categories))
		{
			return;
		}

		$html = '<div id="product_wt_custom_fields" class="main-card">';
		$html .= '<div class="p-3 mb-3 shadow-sm">
			<h4>Custom fields</h4>
			<p>Вы можете создать необходимые Вам поля в настройках аддона в Опции - Дополнения - wt_jshopping_custom_fields</p>
		</div>';
		$html .= HTMLHelper::_('uitab.startTabSet', 'wt_custom_fields_Tab', array('active' => $languages->lang[0]->lang));
		// Мультиязычные поля
		foreach ($languages as $language)
		{
			$form_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><form></form>');
			$fieldset  = $form_data->addChild('fieldset');
			$fieldset->addAttribute('name', 'wt_jshopping_custom_fields_' . $language->lang);
			$html .= HTMLHelper::_('uitab.addTab', 'wt_custom_fields_Tab', 'wt_custom_fields_Tab_' . $language->lang, 'Custom fields (' . $language->lang . ')');
			foreach ($addon_params->wt_jshopping_custom_fields_categories as $custom_field)
			{

				$custom_field = (object) $custom_field;
				$field        = $fieldset->addChild('field');
				$field->addAttribute('type', $custom_field->field_type);
				$field->addAttribute('label', $custom_field->field_admin_label . ' (' . $language->lang . ')');
				$field->addAttribute('name', OutputFilter::stringURLSafe($custom_field->field_admin_label . '_' . $language->lang));
				$field->addAttribute('class', 'form-control');
				if ($custom_field->field_type == 'editor')
				{
					$field->addAttribute('filter', 'JComponentHelper::filterText');
				}
				elseif ($custom_field->field_type == 'text' || $custom_field->field_type == 'textarea')
				{
					$field->addAttribute('filter', 'safehtml');
				}
				if (!empty($custom_field->field_note))
				{
					$field->addAttribute('description', Text::_($custom_field->field_note));
				}


			}
			$xml  = $form_data->asXML();
			$form = Form::getInstance('wt_jshopping_custom_fields_' . $language->lang, $xml, array("control" => "wt_jshopping_custom_fields[" . $language->lang . "]"));
			if (isset($custom_related_products_json->{$language->lang}))
			{
				$form->bind($custom_related_products_json->{$language->lang});
			}
			$html .= $form->renderFieldset('wt_jshopping_custom_fields_' . $language->lang);

			$html .= HTMLHelper::_('uitab.endTab');
		}

		$html .= HTMLHelper::_('uitab.endTabSet');


		$html .= '</div>';
		 //tmp_html_end
		if(isset($view->etemplatevar) && !empty($view->etemplatevar)){
			$view->etemplatevar .= '<tr><td colspan="2">'.$html.'</td></tr>';
		} else {
			$view->etemplatevar = '<tr><td colspan="2">'.$html.'</td></tr>';
		}

	}

	/**
	 * Сохраняем данные кастомных полей в свою таблицу при сохранении категории.
	 * @param $event Event
	 *
	 * @since 1.0.0
	 */
	public function onAfterSaveCategory(Event $event): void
    {
        /** @var $category */
        $category = $event->getArgument(0);

		$wt_jshopping_custom_fields = $this->getApplication()->getInput()->post->get('wt_jshopping_custom_fields','','raw');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if (!$wt_jshopping_custom_fields || count((array) $wt_jshopping_custom_fields) == 0)
		{
			$wt_jshopping_custom_fields = json_encode(array());
		}
		else
		{
			$wt_jshopping_custom_fields = json_encode($wt_jshopping_custom_fields);
		}
		$query = "REPLACE INTO `#__jshopping_custom_fields_values` SET `category_id`='" . $db->escape($category->category_id) . "', `field_value`='" . $db->escape($wt_jshopping_custom_fields) . "'";
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Удаляем данные кастомных полей для категории при удалении категории.
	 * @param $event Event
	 *
	 * @since 1.0.0
	 */
	public function onAfterRemoveCategory(Event $event): void
	{
        $category_ids = (array) $event->getArgument(0);

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query      = $db->getQuery(true);
		$conditions = array(
			$db->quoteName('category_id') . ' IN (' . implode(',', $category_ids) . ')'
		);
		$query->delete($db->quoteName('#__jshopping_custom_fields_values'))
			->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
	}
}
?>