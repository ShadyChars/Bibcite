# Bibcite
* Contributors: https://github.com/ShadyChars/
* Tags: references, bibtex, CSL, RIS, citations
* Requires at least: 3.1.0
* Tested up to: 4.9.5
* Stable tag: 1.0.0
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bibcite adds and formats scholarly references in WordPress content. It supports Bibtex input files and formats their contents using <abbr>CSL</abbr>.

## Description

Bibcite consumes online BibTeX files to give you access to scholarly references within your posts and pages. It allows you insert standalone bibliographies via the `[bibtex]` shortcode, and to insert notes and a corresponding bibliography with the `[bibshow]...[/bibshow]` and `[bibcite]` shortcodes.

Source reference data is parsed from [BibTeX](http://bibtex.org) files available at URLs specified via shortcode (e.g. `[bibtex file=http://example.com/my-library.bib]`). Where a shortcode does not specify a library, a default library location can be set.

Individual references are transformed into <abbr>HTML</abbr> using a specified [Citation Style Language](http://citationstyles.org/) (<abbr title="Citation Style Language">CSL</abbr>) style, and are then collected into groups for display using a specified [Twig](https://twig.symfony.com/) template (e.g. `[bibtex style=ieee style=my-ordered-list]`). Again, styles and templates can be specified via shortcode or, if omitted, will be replaced by default values.

## Acknowledgements

Bibcite is inspired by and shares a number of concepts with Benjamin Piwowarski's [Papercite plugin](http://www.bpiwowar.net/wp-content/plugins/papercite/documentation/). It is built on Devin Vinson's [WordPress Plugin Boilerplate](http://wppb.io/). Thank you both!

Thanks also to the authors and maintainers of the following projects:

* [Apache log4php](https://logging.apache.org/log4php/)
* [Citation Style Language](https://citationstyles.org/)
* [cocur/slugify](https://logging.apache.org/log4php/)
* [geissler/converter](https://github.com/geissler/converter)
* [PSR Log](https://github.com/php-fig/log)
* [renanbr/bibtex-parser](https://github.com/renanbr/bibtex-parser)
* [seboettg/citeproc-php](https://github.com/seboettg/citeproc-php)
* [Twig](https://twig.symfony.com)

## Installation

This section describes how to install the plugin and get it working.

### Build

Bibcite is not yet available in a ready-to-install form. To obtain and build a copy:

1. Ensure you have installed PHP 7.0 or above
1. Clone or download this repository
1. Run `composer install` to retrieve all dependencies. If you do not have [Composer](https://getcomposer.org/) already, you may install it either [locally or globally](https://getcomposer.org/doc/00-intro.md) as desired.

### Deploy

Having obtained and built the plugin:

1. Upload the contents of the local `bibcite` folder to a new `/wp-content/plugins/bibcite` directory
1. Activate the plugin through the WordPress 'Plugins' menu
1. Configure default values via 'Settings' &rarr; 'Bibcite'

## Usage

Bibcite allows you to insert standalone bibliographies and/or notes and citation into WordPress posts and pages. Citations are loaded from a specified BibTeX file before being formatted using either a built-in or custom <abbr>CSL</abbr> style file and then assembled into a note or bibliography using a built-in or custom Twig template.

Note that where practicable, shortcode and attribute usage mirror those of Benjamin Piwowarski's [Papercite plugin](http://www.bpiwowar.net/wp-content/plugins/papercite/documentation/).

### Standalone Bibliographies

A standalone bibliography is added by the non-enclosing `[bibtex]` shortcode. The shortcode's attributes are processed to discover the content and style of the bibliograpy before being rendered into <abbr>HTML</abbr>. The shortcode accepts the following attributes:

| Attribute  | Value  | Examples  |
|---|---|---|
| `file` | A <abbr>URL</abbr> pointing to a BibTeX library available online. Local files are not supported. If not specified, the default library will be used. | `url=http://example.org/my-library.bib`  |
| `key` | A comma-separated list of citation keys. If a given key is not present in the associated BibTeX library, it will be ignored. | `key=Smith2018`<br/>`key=Smith2018,Jones1992` |
| `style` | The name of a <abbr>CSL</abbr> style with which individual citations will be rendered. If not specified, the default `[bibtex]` style will be used. | `style=ieee`<br/>`style=chicago-fullnote-bibliography` |
| `template` | The name of a Twig template with which rendered citations will be transformed into <abbr>HTML</abbr>.  If not specified, the default `[bibtex]` template will be used. | `template=bibtex-unordered-list`  |
| `sort` | If present, the name of a <abbr>CSL</abbr> schema value by which to sort all bibliography entries. | `sort=title`<br/>`sort=author`<br/>`sort=issued` |
| `order`  | If present, selects either `asc`ending or `desc`ending sort order. If no `sort` attribute is present, this has no effect.  | `order=asc`<br/>`order=desc` |

### Notes and Citations

To create a bibliography from citations made within the text of a post or page, use the enclosing `[bibshow]`...`[/bibshow]` along with the non-enclosing `[bibcite]` shortcodes. The opening and closing `[bibshow]` shortcodes "capture" all citations made within them using `[bibcite]` shortcodes, and, having done so, generate and emit the resulting bibliography at the location of the closing `[/bibshow]` shortcode.

The opening `[bibshow]` shortcode supports the following attributes:

| Attribute  | Value  | Examples  |
|---|---|---|
| `file` | A <abbr>URL</abbr> pointing to a BibTeX library available online. Local files are not supported. If not specified, the default library will be used. | `url=http://example.org/my-library.bib`  |
| `style` | The name of a <abbr>CSL</abbr> style with which individual citations within the generated bibliography will be rendered. If not specified, the default `[bibshow]` style will be used. | `style=ieee`<br/>`style=chicago-fullnote-bibliography` |
| `template` | The name of a Twig template with which individual rendered citations will be combined into an <abbr>HTML</abbr> bibliography.  If not specified, the default `[bibshow]` template will be used. | `template=bibshow-definition-list`  |

`[bibcite]` shortcodes support the following attributes:

| Attribute  | Value  | Examples  |
|---|---|---|
| `key` | A comma-separated list of citation keys. If a given key is not present in the associated BibTeX library, it will be ignored. | `key=Smith2018`<br/>`key=Smith2018,Jones1992` |
| `style` | The name of a <abbr>CSL</abbr> style with which individual citations will be rendered. If not specified, the default `[bibcite]` style will be used. | `style=ieee`<br/>`style=chicago-fullnote-bibliography` |
| `template` | The name of a Twig template with which individual rendered citations will be transformed into an <abbr>HTML</abbr> note.  If not specified, the default `[bibcite]` template will be used. | `template=bibcite-numbered-note`  |

Here's an example of how `[bibshow]`...`[/bibshow]` and `[bibcite]` shortcode work together to create notes and an accompanying bibliography:

```html
[bibshow]
This is a note referring to a single source.[bibcite key=Reference1] And this is another.[bibcite key=Reference3]

This is a note referring to two sources.[bibcite key=Reference1,Reference2]
[/bibshow]
```

This generates three notes in the text itself:

* The first refers to `Reference1`
* The second refers to `Reference3`
* The third refers to `Reference1` *and* `Reference2`

The closing `[/bibshow]` shortcode is replaced by a bibliography that contains the set of all such references in the order in which they were encountered:

1. `Reference1`
2. `Reference3`
3. `Reference2`

Note that `Reference1` appears only once in the generated bibliography.

The exact appearance of the generated notes and citations is dependent on the selected <abbr>CSL</abbr> styles and Twig templates for the `[bibcite]` and `[bibshow]` shortcodes. See the next section for details.

### Styles, Templates and the Rendering Process

All three support shortcodes apply the same rendering process:

1. Find the set of referenced citations
2. Render each citation *individually* using a specified <abbr>CSL</abbr> style (taken from the shortcode's `style` attribute or inherited from the default value in the settings)
3. Combine the individual rendered citations into a single chunk of <abbr>HTML</abbr> using a specified Twig template (taken from the shortcode's `template` attribute or inherited from the default value in the settings)

In the case of `[bibtex]` and `[bibshow]` shortcodes, the set of referenced citations are those to be displayed as part of the generated bibliography. In the case of `[bibcite]` shortcodes, the set of references citations are those referred to by the shortcode's `key` attribute.

In all cases, all relevant rendered citations are presented to the Twig template engine as an array (called `$entries`) of associative arrays with well-defined structures. As an example:

```php
$entries = array(
    array(
        "index" => 1,           // 1-based numeric index
        "key" => "Reference1",  // Citation key
        "csl" => "...",         // CSL entry as JSON string
        "entry" => "..."        // Rendered citation
    ),
    array(
        "index" => 2,
        "key" => "Reference2",
        ...
    ),
    ...
);
```

A simple Twig template to render `$entries` as a bibliography might look like this:

```twig
<dl>
{% for entry in entries %}
    <dt>{{ entry.index + 1 }}.</dt>
    <dd>{{ entry.entry | raw }}</dd>
{% endfor %}
</dl>
```

### Custom styles and templates

Bibcite has a large number of built-in <abbr>CSL</abbr> styles, all taken from the [official repository of <abbr>CSL</abbr> styles](https://github.com/citation-style-language/styles/). These are available via autocomplete from the 'Settings' page. Additionally, Bibcite comes with a limited set of Twig templates:

* `bibtex-unordered-list` is intended to render `[bibtex]` bibliographies as simple unordered lists
* `bibcite-numbered-note` and `bibshow-definition-list` are intended to be used together to present `[bibtex]` and `[bibshow]` shortcodes as numbered notes pointing to a numbered bibliography

Again, these are available via autocomplete from the 'Settings' page.

However, it is possible to add both user-defined <abbr>CSL</abbr> styles and Twig templates. Styles (with the extension `.csl`) should be placed in the `styles` directory and templates (with the extension `.twig`) should be placed in the `templates` directory; having done so, custom styles can now be set as defaults in the 'Settings' page or used via `style` or `template` shortcode attributes as required.

## Known Issues

* Only remote libraries are supported
* Only BibTeX libraries are supported
* The "cache cleared" admin message erroneously reappears after settings are saved
* There are no unit tests

## Changelog

### 1.0.0
First version.