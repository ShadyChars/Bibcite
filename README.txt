=== Bibcite ===
Contributors: https://github.com/ShadyChars/
Donate link: https://github.com/ShadyChars/Bibcite/
Tags: references, bibtex, CSL, RIS, citations
Requires at least: 3.1.0
Tested up to: 4.9.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bibcite helps add and format scholarly references. It support Bibtex input files and uses CSL to format notes, references and bibliographies.

== Description ==

Bibcite consumes online Bibtex files to give you access to scholarly references within your posts and pages. It allows you insert standalone bibliographies via the `[bibtex]` shortcode, and to insert notes and a corresponding bibliography with the `[bibshow]...[/bibshow]` and `[bibcite]` shortcodes. 

Source reference data is parsed from [Bibtex](http://bibtex.org) files available at URLs specified via shortcode (e.g. `[bibtex file=http://example.com/my-library.bib]`). Where a shortcode does not specify a library, a default library location can be set.

Individual references are transformed into HTML using a specified [Citation Style Language](http://citationstyles.org/) (<abbr title="Citation Style Language">CSL</abbr>) style, and are then collected into groups for display using a specified [Twig](https://twig.symfony.com/) template. Again, styles and templates can be specified via shortcode or, if omitted, will be replaced by default values.

== Installation ==

This section describes how to install the plugin and get it working.

=== Building ===

Bibcite is not yet available in a ready-to-install form. To obtain and build a copy:

1. Ensure you have installed PHP 7.0 or above
1. Clone or download this repository
1. Run `php composer install` to install all dependencies. If you do not have [Composer](https://getcomposer.org/) installed already, you must first install it either [locally or globally](https://getcomposer.org/doc/00-intro.md) as desired. 

=== Deploying === 

Having obtained and built the plugin:

1. Upload the contents of the local `bibcite` folder to a new `/wp-content/plugins/bibcite` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure default values via 'Settings' &rarr; 'Bibcite'

== Usage == 

=== Standalone Bibliographies ===

=== Notes and Citations ===

=== Advanced ===

TK

* Cache clearing
* Log files

== Screenshots ==

To come.

== Known Issues == 

* Only BibTeX

== Changelog ==

= 1.0 =
First version.

== Acknowledgements == 

Bibcite is inspired by ...

Bibcite uses the following libraries:

* ...