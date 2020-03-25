<?php

/**
 * Class Page
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  VuFind\Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace VuFind\Content;

/**
 * Class Page
 *
 * @category VuFind
 * @package  Content
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Page
{
    /**
     * Types/formats of content
     *
     * @var array $types
     */
    protected $types = [
        'phtml',
        'md',
    ];

    /**
     * Theme info service
     *
     * @var \VuFindTheme\ThemeInfo
     */
    protected $themeInfo;

    /**
     * Translator
     *
     * @var \Laminas\Mvc\I18n\Translator
     */
    protected $translator;

    /**
     * Default language
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * Page constructor.
     *
     * @param \VuFindTheme\ThemeInfo       $themeInfo  Theme information service
     * @param \Laminas\Mvc\I18n\Translator $translator Translator
     * @param \Laminas\Config\Config       $config     Main configuration
     */
    public function __construct($themeInfo, $translator, $config)
    {
        $this->themeInfo = $themeInfo;
        $this->translator = $translator;
        $this->defaultLanguage  = $config->Site->language;
    }

    /**
     * Try to find template information about desired page
     *
     * @param string $pathPrefix Subdirectory where the template should be located
     * @param string $pageName   Template name
     *
     * @return array|null Null if template is not found or array with keys renderer
     *                    (type of template), path (full path of template), page
     *                    (page name)
     */
    public function determineTemplateAndRenderer($pathPrefix, $pageName)
    {
        $language = $this->translator->getLocale();
        // Try to find a template using
        // 1.) Current language suffix
        // 2.) Default language suffix
        // 3.) No language suffix
        $templates = [
            "{$pageName}_$language",
            "{$pageName}_$this->defaultLanguage",
            $pageName,
        ];
        foreach ($templates as $template) {
            foreach ($this->types as $type) {
                $filename = "$pathPrefix$template.$type";
                $path = $this->themeInfo->findContainingTheme($filename, true);
                if (null != $path) {
                    return [
                        'renderer' => $type,
                        'path' => $path,
                        'page' => $template,
                    ];
                }
            }
        }

        return null;
    }
}