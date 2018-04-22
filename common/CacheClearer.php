<?php

namespace Bibcite\Common;

/**
 * Fired by the custom BIBCITE_SC_CLEAR_CACHE_ACTION. Instructs classes that cache data to clear
 * out those caches.
 *
 * @link       https://github.com/OrkneyDullard/bibcite
 * @since      1.0.0
 *
 * @package    Bibcite/Common
 */

/**
 * Fired by the custom BIBCITE_SC_CLEAR_CACHE_ACTION. Instructs classes that cache data to clear
 * out those caches.
 *
 * This class defines all code necessary to clear out temporary caches.
 *
 * @since      1.0.0
 * @package    Bibcite/Common
 * @author     Keith Houston <keith@shadycharacters.co.uk>
 */
class CacheClearer
{

    /**
     * Fired by the custom BIBCITE_SC_CLEAR_CACHE_ACTION. Instructs classes that cache data to clear
     * out those caches.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function clear_cache()
    {
        \Bibcite\Common\Downloader::clear_cache();
        \Bibcite\Common\CslLibrary::clear_cache();
    }

}
