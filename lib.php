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
 * This file contains main class for the catalog
 *
 * @package    local
 * @subpackage catalog
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mentor_core\training;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/edadmin/lib.php');
require_once($CFG->dirroot . '/local/catalog/classes/controllers/catalog_controller.php');

/**
 * Generate a dictionnary of words for the trainings search
 *
 * @param array $trainings The list of the trainings
 * @return array
 */
function local_catalog_get_dictionnary($trainings) {

    $dictionnary = [];

    foreach ($trainings as $training) {
        // Implode all word of the training.
        $words = implode(' ', array_filter(array_map('strtolower', [
            $training->name ?? '',
            $training->typicaljob ?? '',
            $training->skills ?? '',
            strip_tags($training->content) ?? '',
            $training->idsirh ?? '',
            $training->producingorganization ?? '',
            $training->producerorganizationshortname ?? '',
            $training->catchphrase ?? '',
            $training->entityname ?? '',
            $training->entityfullname ?? '',
        ])));

        $words = local_catalog_clean_string($words);

        // Remove break line.
        $words = preg_replace("/<br>|\n|<br( ?)\/>/", " ", $words);

        // Add the training words into the dictionnary.
        $dictionnary[$training->id] = $words;
    }

    return $dictionnary;
}

/**
 * Clean up strings from accents
 *
 * @param string $text
 * @return string|string[]|null
 */
function local_catalog_clean_string($text) {
    $utf8 = [
        '/[áàâãªä]/u' => 'a',
        '/[ÁÀÂÃÄ]/u' => 'A',
        '/[ÍÌÎÏ]/u' => 'I',
        '/[íìîï]/u' => 'i',
        '/[éèêë]/u' => 'e',
        '/[ÉÈÊË]/u' => 'E',
        '/[óòôõºö]/u' => 'o',
        '/[ÓÒÔÕÖ]/u' => 'O',
        '/[úùûü]/u' => 'u',
        '/[ÚÙÛÜ]/u' => 'U',
        '/[ýÿ]/u' => 'y',
        '/[ÝŸ]/u' => 'Y',
        '/ç/' => 'c',
        '/Ç/' => 'C',
        '/ñ/' => 'n',
        '/Ñ/' => 'N',
        '/–/' => '-', // UTF-8 hyphen to "normal" hyphen.
        '/[’‘‹›‚]/u' => ' ', // Literally a single quote.
        '/[“”«»„]/u' => ' ', // Double quote.
        '/ /' => ' ', // Nonbreaking space (equiv. to 0x160).
    ];

    return preg_replace(array_keys($utf8), array_values($utf8), $text);
}


/**
 * Add <p> tag for accessibility around content if not present
 * 
 * @param string $content The content to ensure is wrapped in a <p> tag.
 * @return string The content wrapped in a <p> tag if it wasn't already.
 */
function ensureParagraphTag($content) {
    if (preg_match('/^<p.*?>.*<\/p>$/s', $content)) {
        return $content;
    
    } else if (preg_match('/<br\s*\/?>/i', $content)) {
        $trimmedContent = preg_replace('/^(<br\s*\/?>)+|(<br\s*\/?>)+$/i', '', $content);
        $contentWithParagraphs = '<p>' . preg_replace('/<br\s*\/?>+/i', '</p><p>', $trimmedContent) . '</p>';
        
        return $contentWithParagraphs;
        
    } else {
        return '<p>' . $content . '</p>';
    }
}




