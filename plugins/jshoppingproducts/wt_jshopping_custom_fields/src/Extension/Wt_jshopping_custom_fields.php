<?php

namespace Joomla\Plugin\Jshoppingproducts\Wt_jshopping_custom_fields\Extension;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

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
            'onBeforeDisplayCategoryView' => 'onBeforeDisplayCategoryView',
            'onBeforeDisplayProductListView' => 'onBeforeDisplayProductListView',
            'onBeforeDisplayProductView' => 'onBeforeDisplayProductView'
        ];
    }

    /**
     * @param Event $event
     * @return void
     * @since 1.0.0
     */
    public function onBeforeDisplayCategoryView(Event $event): void
    {
        /** @var $view object */
        $view = $event->getArgument(0);

        $view->wt_jshopping_custom_fields = []; // Для кастомного рендера в шаблоне
        $lang = $this->getApplication()->getLanguage()->getLocale();
        $current_lang_locale = $lang[8]; // "ru", "en" etc
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $addon = \JSFactory::getTable('addon');
        $addon->loadAlias('wt_jshopping_custom_fields');
        $addon_params = (object)$addon->getParams();

        // Собираем массив полей для категории
        $fields_params = [];
        foreach ($addon_params->wt_jshopping_custom_fields_categories as $field_param) {
            $field = [
                'field_front_label' => $field_param['field_front_label'],
                'position' => $field_param['position'],
                'layout_category' => $field_param['layout_category'],
                'layout_parent_category' => $field_param['layout_parent_category'],
                'enable_field_data_in_custom_object' => $field_param['enable_field_data_in_custom_object'],
            ];
            $fields_params[OutputFilter::stringURLSafe($field_param['field_admin_label'] . '_' . $current_lang_locale)] = $field;
        }
        /**
         * Получаем список значений полей только на те id, которые в данный момент отображаются
         */
        if (!empty($view->category->category_id) || !empty($view->categories)) {
            $category_ids = [$view->category->category_id];
            foreach ($view->categories as $category_id) {
                $category_ids[] = $category_id->category_id;
            }


            $query = $db->getQuery(true);
            $query->select('*')
                ->from($db->quoteName('#__jshopping_custom_fields_values'))
                ->where($db->quoteName('category_id') . ' IN(' . implode(',', $category_ids) . ')');

            $db->setQuery($query);
            $list_fields = $db->loadObjectList(); // данные кастомных полей для категории и подкатегорий


            // Выбираем поля для текущей категории
            foreach ($list_fields as $field) {

                if ($view->category->category_id == $field->category_id) {
                    $field_values = json_decode($field->field_value);
                    if ($field_values->$current_lang_locale) {

                        foreach ($field_values->$current_lang_locale as $key => $field_value) {
                            /**
                             * Подключаем лейауты для своих макетов вывода
                             */
                            $layout_id = $fields_params[$key]['layout_category'] ? $fields_params[$key]['layout_category'] : 'default';
                            $layout = new FileLayout($layout_id);
                            $layout->addIncludePath('components/com_jshopping/addons/wt_jshopping_custom_fields/layouts/productlist');
                            $label = $fields_params[$key]['field_front_label'];

                            // Добавляем параметры поля к каждому значению
                            if (!empty($field_value) &&
                                $fields_params[$key]['position'] &&
                                $fields_params[$key]['position'] != 'none') {


                                if ($fields_params[$key]['position'] == 'custom_position') {
                                    $position = $fields_params[$key]['custom_position'];
                                } else {
                                    $position = $fields_params[$key]['position'];
                                }

                                $view->$position .= $layout->render([
                                    'label' => $label,
                                    'field_value' => $field_value
                                ]);

                            }
                            // Для программного доступа в шаблоне
                            if ($fields_params[$key]['enable_field_data_in_custom_object'] == true) {
                                $view->wt_jshopping_custom_fields['category'][$key] = [
                                    'label' => $label,
                                    'field_value' => $field_value
                                ];
                            }
                        }
                    }
                }
            }


            if ($list_fields) {

                foreach ($view->categories as $category) {
                    foreach ($list_fields as $field) {
                        if ($category->category_id == $field->category_id) {
                            $field_values = json_decode($field->field_value);
                            if ($field_values->$current_lang_locale) {

                                foreach ($field_values->$current_lang_locale as $key => $field_value) {
                                    /**
                                     * Подключаем лейауты для своих макетов вывода
                                     */
                                    $layout_id = $fields_params[$key]['layout_parent_category'] ? $fields_params[$key]['layout_parent_category'] : 'default';
                                    $layout = new FileLayout($layout_id);
                                    $layout->addIncludePath('components/com_jshopping/addons/wt_jshopping_custom_fields/layouts/productlist');
                                    $label = $fields_params[$key]['field_front_label'];
                                    // Добавляем параметры поля к каждому значению
                                    if (!empty($field_value) &&
                                        $fields_params[$key]['position'] &&
                                        $fields_params[$key]['position'] != 'none') {


                                        if ($fields_params[$key]['position'] == 'custom_position') {
                                            $position = $fields_params[$key]['custom_position'];
                                        } else {
                                            $position = $fields_params[$key]['position'];
                                        }

                                        $category->$position .= $layout->render([
                                            'label' => $label,
                                            'field_value' => $field_value
                                        ]);

                                    }
                                    // Для программного доступа в шаблоне
                                    if ($fields_params[$key]['enable_field_data_in_custom_object']) {
                                        $view->wt_jshopping_custom_fields['subcategories'][$category->category_id][$key] = [
                                            'label' => $label,
                                            'field_value' => $field_value
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Event $event
     * @return void
     * @throws \Exception
     * @since 1.0.0
     */
    public function onBeforeDisplayProductListView(Event $event): void
    {
        $view = $event->getArgument(0);
        $product_list = $event->getArgument(1);

        $view->wt_jshopping_custom_fields = []; // Для кастомного рендера в шаблоне
        $lang = $this->getApplication()->getLanguage()->getLocale();
        $current_lang_locale = $lang[8]; // "ru", "en" etc
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $addon = \JSFactory::getTable('addon');
        $addon->loadAlias('wt_jshopping_custom_fields');
        $addon_params = (object)$addon->getParams();

        // Собираем массив полей для категории
        $fields_params = [];
        foreach ($addon_params->wt_jshopping_custom_fields_categories as $field_param) {
            $field = [
                'field_front_label' => $field_param['field_front_label'],
                'position' => $field_param['position'],
                'layout_category' => $field_param['layout_category'],
                'layout_parent_category' => $field_param['layout_parent_category'],
                'enable_field_data_in_custom_object' => $field_param['enable_field_data_in_custom_object'],
            ];
            $fields_params[OutputFilter::stringURLSafe($field_param['field_admin_label'] . '_' . $current_lang_locale)] = $field;
        }
        /**
         * Получаем список значений полей только на те id, которые в данный момент отображаются
         */
        if (!empty($view->category->category_id) || !empty($view->categories)) {
            $category_ids = [$view->category->category_id];
            foreach ($view->categories as $category_id) {
                $category_ids[] = $category_id->category_id;
            }


            $query = $db->getQuery(true);
            $query->select('*')
                ->from($db->quoteName('#__jshopping_custom_fields_values'))
                ->where($db->quoteName('category_id') . ' IN(' . implode(',', $category_ids) . ')');

            $db->setQuery($query);
            $list_fields = $db->loadObjectList(); // данные кастомных полей для категории и подкатегорий


            // Выбираем поля для текущей категории
            foreach ($list_fields as $field) {

                if ($view->category->category_id == $field->category_id) {
                    $field_values = json_decode($field->field_value);
                    if ($field_values->$current_lang_locale) {

                        foreach ($field_values->$current_lang_locale as $key => $field_value) {
                            /**
                             * Подключаем лейауты для своих макетов вывода
                             */
                            $layout_id = $fields_params[$key]['layout_category'] ? $fields_params[$key]['layout_category'] : 'default';
                            $layout = new FileLayout($layout_id);
                            $layout->addIncludePath('components/com_jshopping/addons/wt_jshopping_custom_fields/layouts/productlist');
                            $label = $fields_params[$key]['field_front_label'];

                            // Добавляем параметры поля к каждому значению
                            if (!empty($field_value) &&
                                $fields_params[$key]['position'] &&
                                $fields_params[$key]['position'] != 'none') {


                                if ($fields_params[$key]['position'] == 'custom_position') {
                                    $position = $fields_params[$key]['custom_position'];
                                } else {
                                    $position = $fields_params[$key]['position'];
                                }

                                $view->$position .= $layout->render([
                                    'label' => $label,
                                    'field_value' => $field_value
                                ]);

                            }
                            // Для программного доступа в шаблоне
                            if ($fields_params[$key]['enable_field_data_in_custom_object'] == true) {
                                $view->wt_jshopping_custom_fields['category'][$key] = [
                                    'label' => $label,
                                    'field_value' => $field_value
                                ];
                            }
                        }
                    }
                }
            }


            if ($list_fields) {

                foreach ($view->categories as $category) {
                    foreach ($list_fields as $field) {
                        if ($category->category_id == $field->category_id) {
                            $field_values = json_decode($field->field_value);
                            if ($field_values->$current_lang_locale) {

                                foreach ($field_values->$current_lang_locale as $key => $field_value) {
                                    /**
                                     * Подключаем лейауты для своих макетов вывода
                                     */
                                    $layout_id = $fields_params[$key]['layout_parent_category'] ? $fields_params[$key]['layout_parent_category'] : 'default';
                                    $layout = new FileLayout($layout_id);
                                    $layout->addIncludePath('components/com_jshopping/addons/wt_jshopping_custom_fields/layouts/productlist');
                                    $label = $fields_params[$key]['field_front_label'];
                                    // Добавляем параметры поля к каждому значению
                                    if (!empty($field_value) &&
                                        $fields_params[$key]['position'] &&
                                        $fields_params[$key]['position'] != 'none') {


                                        if ($fields_params[$key]['position'] == 'custom_position') {
                                            $position = $fields_params[$key]['custom_position'];
                                        } else {
                                            $position = $fields_params[$key]['position'];
                                        }

                                        $category->$position .= $layout->render([
                                            'label' => $label,
                                            'field_value' => $field_value
                                        ]);

                                    }
                                    // Для программного доступа в шаблоне
                                    if ($fields_params[$key]['enable_field_data_in_custom_object']) {
                                        $view->wt_jshopping_custom_fields['subcategories'][$category->category_id][$key] = [
                                            'label' => $label,
                                            'field_value' => $field_value
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Собираем массив полей для товаров
        $fields_params = [];
        foreach ($addon_params->wt_jshopping_custom_fields_products as $field_param) {
            $field = [
                'field_front_label' => $field_param['field_front_label'],
                'position_in_category' => $field_param['position_in_category'],
                'position_in_category_custom_position' => $field_param['position_in_category_custom_position'],
                'layout' => $field_param['layout_productlist'],
                'enable_field_data_in_custom_object' => $field_param['enable_field_data_in_custom_object'],
            ];
            $fields_params[OutputFilter::stringURLSafe($field_param['field_admin_label'] . '_' . $current_lang_locale)] = $field;
        }


        /**
         * Получаем список значений полей только на те id, которые в данный момент отображаются
         */
        $product_ids = [];
        foreach ($view->rows as $product) {
            $product_ids[] = $product->product_id;
        }

        if (count($product_ids) == 0) {
            return;
        }

        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->quoteName('#__jshopping_custom_fields_values'))
            ->where($db->quoteName('product_id') . ' IN(' . implode(',', $product_ids) . ')');
        $db->setQuery($query);
        $list_fields = $db->loadObjectList();

        if ($list_fields) {

            foreach ($view->rows as $product) {
                foreach ($list_fields as $field) {
                    if ($product->product_id == $field->product_id) {
                        $field_values = json_decode($field->field_value);
                        if ($field_values->$current_lang_locale) {

                            foreach ($field_values->$current_lang_locale as $key => $field_value) {
                                /**
                                 * Подключаем лейауты для своих макетов вывода
                                 */
                                $layout_id = $fields_params[$key]['layout'] ? $fields_params[$key]['layout'] : 'default';
                                $layout = new FileLayout($layout_id);
                                $layout->addIncludePath('components/com_jshopping/addons/wt_jshopping_custom_fields/layouts/productlist1');
                                $label = $fields_params[$key]['field_front_label'];

                                // Добавляем параметры поля к каждому значению
                                if (!empty($field_value) &&
                                    $fields_params[$key]['position_in_category'] &&
                                    $fields_params[$key]['position_in_category'] != 'none') {

                                    if ($fields_params[$key]['position_in_category'] == 'custom_position') {
                                        $position = $fields_params[$key]['position_in_category_custom_position'];
                                    } else {
                                        $position = $fields_params[$key]['position_in_category'];
                                    }

                                    $view->$position .= $layout->render([
                                        'label' => $label,
                                        'field_value' => $field_value
                                    ]);
                                }
                                // Для программного доступа в шаблоне
                                if ($fields_params[$key]['enable_field_data_in_custom_object']) {
                                    $view->wt_jshopping_custom_fields['products'][$product->product_id][$key] = [
                                        'label' => $label,
                                        'field_value' => $field_value
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Event $event
     * @return void
     * @throws \Exception
     */
    public function onBeforeDisplayProductView(Event $event): void
    {
        $view = $event->getArgument(0);

        $view->wt_jshopping_custom_fields = ['test', 'test2'];
        print_r($view->wt_jshopping_custom_fields);

        $product_id = $view->product->product_id;
        $lang = $this->getApplication()->getLanguage()->getLocale();
        $current_lang_locale = $lang[8]; // "ru", "en" etc

        $addon = \JSFactory::getTable('addon');
        $addon->loadAlias('wt_jshopping_custom_fields');
        $addon_params = (object)$addon->getParams();
        if (empty($addon_params->wt_jshopping_custom_fields_products)) {
            return;
        }

        // Собираем массив полей и их параметров
        $fields_params = [];
        foreach ($addon_params->wt_jshopping_custom_fields_products as $field_param) {
            $field = [
                'field_front_label' => $field_param['field_front_label'],
                'position_in_product_custom_position' => $field_param['position_in_product_custom_position'],
                'position_in_product' => $field_param['position_in_product'],
                'layout' => $field_param['layout_product'],
                'enable_field_data_in_custom_object' => $field_param['enable_field_data_in_custom_object'],
            ];
            $fields_params[OutputFilter::stringURLSafe($field_param['field_admin_label'] . '_' . $current_lang_locale)] = $field;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->quoteName('#__jshopping_custom_fields_values'))
            ->where($db->quoteName('product_id') . ' = ' . $db->quote($product_id));
        $db->setQuery($query);

        $list_fields = $db->loadObject();

        if ($list_fields) {
            $field_values = json_decode($list_fields->field_value);
            if ($field_values->$current_lang_locale) {
                $view->wt_jshopping_custom_fields = []; // Для кастомного рендера в шаблоне
                foreach ($field_values->$current_lang_locale as $key => $field_value) {
                    // Добавляем параметры поля к каждому значению
                    if (!empty($field_value) &&
                        $fields_params[$key]['position_in_product'] &&
                        $fields_params[$key]['position_in_product'] != 'none') {
                        $label = $fields_params[$key]['field_front_label'];

                        if ($fields_params[$key]['position_in_product'] == 'custom_position') {
                            $position = $fields_params[$key]['position_in_product_custom_position'];
                        } else {
                            $position = $fields_params[$key]['position_in_product'];
                        }
                        /**
                         * Подключаем лейауты для своих макетов вывода
                         */
                        $layout_id = $fields_params[$key]['layout'] ? $fields_params[$key]['layout'] : 'default';
                        $layout = new FileLayout($layout_id);
                        $layout->addIncludePath('components/com_jshopping/addons/wt_jshopping_custom_fields/layouts/product');


                        if ($position == '_tmp_var_old_price_ext' ||
                            $position == '_tmp_var_price_ext' ||
                            $position == '_tmp_var_bottom_price' ||
                            $position == '_tmp_var_bottom_allprices') {
                            /**
                             * Почему-то эти 4 позиции указываются в $product, а остальные во $view
                             */

                            $view->product->$position .= $layout->render([
                                'label' => $label,
                                'field_value' => $field_value
                            ]);

                        } else {

                            $view->$position .= $layout->render([
                                'label' => $label,
                                'field_value' => $field_value
                            ]);
                        }

                    }

                    // Для программного доступа в шаблоне
                    if (!empty($field_value) && $fields_params[$key]['enable_field_data_in_custom_object'] == true) {
                        $view->wt_jshopping_custom_fields[$key] = [
                            'label' => $label,
                            'field_value' => $field_value
                        ];
                    }

                }// end foreach
            }
        }
    }
}
?>