# Section Links

## Purpose

**Section Links** analyzes all the links incoming from or arriving to a specific page and shows those pointing to non-existing sections.
See the [MediaWiki documentation](https://www.mediawiki.org/wiki/Help:Links) for more information about internal links and their syntax.

## Hosting

The tool is currently hosted on [Wikimedia Tool Labs](https://tools.wmflabs.org/) at the following address:
https://tools.wmflabs.org/section-links/

It was previously hosted on [Wikimedia Toolserver](https://meta.wikimedia.org/wiki/Toolserver).

## Technical details

The tool is written in PHP.
The tool uses the [MediaWiki API](https://www.mediawiki.org/wiki/API:Main_page) to get the lists of links and the text of pages, which is needed in order to assess the correctness of links that point to specific sections.

Direct access to the database would not be useful, since page text is not replicated on Wikimedia Tool Labs.

## Contacts

You can send any request, bug report, suggestions for improvements or pull request here on GitHub.
Alternatively, you can reach me on [Meta Wikimedia](https://meta.wikimedia.org/wiki/User:Pietrodn).

## License

This software is licensed under the [GNU General Public License, Version 3](https://www.gnu.org/licenses/gpl.html).
You can find a copy if it in the `LICENSE` file.
