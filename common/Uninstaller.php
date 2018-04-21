<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor\autoload.php';

/**
 * Fired during plugin uninstallation
 *
 * @link       https://github.com/OrkneyDullard/bibcite
 * @since      1.0.0
 *
 * @package    Bibcite/Common
 */

/**
 * Fired during plugin uninstallation.
 *
 * This class defines all code necessary to run during the plugin's uninstallation.
 *
 * @since      1.0.0
 * @package    Bibcite/Common
 * @author     Keith Houston <keith@shadycharacters.co.uk>
 */
class Uninstaller
{

    /**
     * Static uninstall method intended to be called when the plugin is uninstalled.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function uninstall()
    {

        // All classes that are uninstallable declare a static uninstall() method.
        \Bibcite\Admin\Admin::uninstall();
        \Bibcite\Common\CslLibrary::uninstall();
    }
}
