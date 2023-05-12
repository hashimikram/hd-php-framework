<?php
namespace HDFramework\src;

use Smarty;

/**
 * HD template rendering engine based on Smarty
 *
 * Dependencies: HDApplication<br />
 * Configurations dependencies:<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDTemplateEngine
{

    private static $templateEngine;

    public static function getTemplateEngine($escapeHtml = true)
    {
        if (! self::$templateEngine) {

            self::$templateEngine = new Smarty();
            self::$templateEngine->setTemplateDir(array(
                'appsViews' => HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'view'
            ));
            self::$templateEngine->addPluginsDir(HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'plugins');
            self::$templateEngine->compile_dir = HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'templates_c';
            self::$templateEngine->config_dir = HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'configs';
            self::$templateEngine->cache_dir = HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
            self::$templateEngine->debugging = false;
            self::$templateEngine->caching = false;
            self::$templateEngine->cache_lifetime = 120;
            if (HDApplication::getEnvType() != "live") {
                self::$templateEngine->force_compile = true;
            }
        }

        self::$templateEngine->escape_html = $escapeHtml;
        return self::$templateEngine;
    }
}
