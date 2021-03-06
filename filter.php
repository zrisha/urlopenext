<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  External link filtering
 *
 *  This filter will replace any external links
 *  with a target of _blank
 *
 * @package    filter
 * @subpackage urlopenext
 */

defined('MOODLE_INTERNAL') || die();

/**
 * External link modifier.
 *
 *
 * @package    filter
 * @subpackage urlopenext
 */

class filter_urlopenext extends moodle_text_filter {
    /** @var bool True if currently filtering trusted text */
    private $trusted;

    public function filter($text, array $options = array()) {
        global $CFG, $PAGE;

        if (!is_string($text) or empty($text)) {
            // non string data can not be filtered anyway
            return $text;
        }

        if (stripos($text, '</a>') === false) {
            // Performance shortcut - if there are no </a> tags, nothing can match.
            return $text;
        }

        // Check SWF permissions.
        $this->trusted = !empty($options['noclean']) or !empty($CFG->allowobjectembed);

        //Find entire a tags and trigger callback
        $result = preg_replace_callback('/<\s*a[^>]*>[\s\S]*?<\s*\/\s*a>/i', array($this, 'callback'), $text);

        // Return the same string except processed by the above.
        return $result;
    }

    /**
     * Replace external link to open in new window.
     *
     * @param array $matches
     * @return string
     */
    private function callback(array $matches) {
        global $CFG, $PAGE;

        //Don't run if string excessively long
        if (strlen($matches[0]) > 4096) {
          return $matches[0];
        }

        //Convert a tag string to php dom
        $dom = new DOMDocument;
        $html_data  =  mb_convert_encoding($matches[0], 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($html_data, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $links = $dom->getElementsByTagName('a');

        //Determine if external or internal
        //Checks if url host matches with moodle hostt
        foreach ($links as $link) {
          $domain = parse_url($CFG->wwwroot);
          $href = parse_url($link->getAttribute('href'));

          if($href === "")
            return $matches[0];

          if(!array_key_exists('host', $href))
            return $matches[0];

          if($domain['host'] !== $href['host']){
            $alert = $dom->createElement('span', ' (new window)');
            $alert->setAttribute('class', "sr-only");

            $icon = $dom->createElement('i');
            $icon->setAttribute('class', "icon fa fa fa-external-link");
            $icon->setAttribute('style', "margin: 0 0 0 .2rem;");

            $link->setAttribute('target', '_blank');
            $link->appendChild($icon);
            $link->appendChild($alert);
            $html = $dom->saveHTML();
            return $html;
          }
        }
        return $matches[0];
      }
    }
