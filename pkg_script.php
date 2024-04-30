<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {

    public function register(Container $container): void
    {
        $container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
            /**
             * The application object
             *
             * @var AdministratorApplication $app
             *
             * @since 1.0.0
             */
            protected AdministratorApplication $app;

            /**
             * The Database object.
             *
             * @var DatabaseDriver $db
             *
             * @since 1.0.0
             */
            protected DatabaseDriver $db;

            /**
             * Minimum Joomla version required to install the extension.
             *
             * @var string $minimumJoomla
             *
             * @since 1.0.0
             */
            protected string $minimumJoomla = '4.3';

            /**
             * Minimum PHP version required to install the extension.
             *
             * @var string $minimumPhp
             *
             * @since 1.0.0
             */
            protected string $minimumPhp = '7.4';

            /**
             * @var array $providersInstallationMessageQueue
             *
             * @since 2.0.3
             */
            protected $providersInstallationMessageQueue = [];

            /**
             * Constructor.
             *
             * @param AdministratorApplication $app The application object.
             *
             * @since 1.0.0
             */
            public function __construct(AdministratorApplication $app)
            {
                $this->app = $app;
                $this->db = Factory::getContainer()->get('DatabaseDriver');
            }

            /**
             * Function called after the extension is installed.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function install(InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Function called after the extension is updated.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function update(InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Function called after the extension is uninstalled.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function uninstall(InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Function called before extension installation/update/removal procedure commences.
             *
             * @param   string            $type     The type of change (install or discover_install, update, uninstall)
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function preflight(string $type, InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Function called after extension installation/update/removal procedure commences.
             *
             * @param   string            $type     The type of change (install or discover_install, update, uninstall)
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function postflight(string $type, InstallerAdapter $adapter): bool
            {
                $element = 'wt_jshopping_custom_fields';
                $name = 'WT Jshopping Custom Fields';
                $version = '1.1.0';
                $uninstallPath = '/components/com_jshopping/addons/' . $element . '/uninstall.php';

                $this->app->enqueueMessage('Внесение изменений в базу данных');
                if ($type != 'uninstall')
                {
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
                    $this->db->setQuery($query);
                    $this->db->execute();

                    include_once JPATH_ROOT . '/components/com_jshopping/bootstrap.php';
                    $addon = \JSFactory::getTable('addon');
                    $addon->loadAlias($element);
                    $addon->set('name', $name);
                    $addon->set('version', $version);
                    $addon->set('uninstall', $uninstallPath);
                    $addon->store();

                    $this->app->enqueueMessage('Аддон установлен');
                }
                else
                {
                    $query = "
                        drop table if exists #__jshopping_custom_fields_values
                    ";
                    $this->db->setQuery($query);
                    $this->db->execute();

                    include_once JPATH_ROOT . '/components/com_jshopping/bootstrap.php';
                    $addon = \JSFactory::getTable('addon');
                    $addon->loadAlias($element);
                    if ($addon->hasPrimaryKey())
                    {
                        $addon->delete();
                        if ($addon->uninstall)
                        {
                            include_once JPATH_ROOT . $addon->uninstall;
                        }

                        $this->app->enqueueMessage('Аддон полностью удален');
                    }
                    else
                    {
                        $this->app->enqueueMessage('Аддон не найден в Базе, возможно он был удален ранее', 'warning');
                    }
                }

                return true;
            }
        });
    }
};