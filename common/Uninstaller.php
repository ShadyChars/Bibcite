<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Fired during plugin uninstallation.
 *
 * This class defines all code necessary to run during the plugin's 
 * uninstallation.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Uninstaller
{

    /**
     * Static uninstall method intended to be called when the plugin is 
     * uninstalled.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function uninstall()
    {

        // All classes that are uninstallable declare a static uninstall() 
        // method.
        \Bibcite\Admin\Admin::uninstall();
        \Bibcite\Common\CslLibrary::uninstall();
        \Bibcite\Common\Transients::uninstall();
    }
}
