<?php

namespace Bibcite\Common;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Activator
{

    /**
     * Static method to be called on plugin activation.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function activate()
    {

        // All classes that are activatable declare a static activate() method.
        \Bibcite\Admin\Admin::activate();
    }

}
