<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Michelf\Markdown;
use Michelf\MarkdownExtra;

// ------------------------------------------------

if (! function_exists('parse_markdown'))
{
	/**
	 * Parse a block of markdown and get HTML back
	 *
	 * @param string $markdown The markdown text.
	 *
	 * @return string The HTML
	 */
	function parse_markdown($markdown)
	{
		return Markdown::defaultTransform($markdown);
	}
}

// ------------------------------------------------

if (! function_exists('parse_markdownextra'))
{
	/**
	 * @param $markdown
	 *
	 * @return string
	 */
	function parse_markdownextra($markdown)
	{
		return MarkdownExtra::defaultTransform($markdown);
	}
}
