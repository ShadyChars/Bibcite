<?php

namespace Bibcite\Common;

/**
 * This class defines all code necessary to clear out temporary caches.
 * 
 * Fired by the custom BIBCITE_CLEAR_CACHE_ACTION. Instructs classes that cache 
 * data to clear out those caches.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class CacheClearer
{

    /**
     * Fired by the custom BIBCITE_CLEAR_CACHE_ACTION. Instructs classes that 
     * cache data to clear out those caches.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function clear_cache()
    {
        \Bibcite\Common\CslLibrary::clear_cache();        
        \Bibcite\Common\Transients::clear_cache();
    }

}
