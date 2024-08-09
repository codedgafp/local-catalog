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
 * Catalog renderer
 *
 * @package    local
 * @subpackage catalog
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog\output;

use local_mentor_core\catalog_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/catalog/lib.php');
require_once($CFG->dirroot . '/local/mentor_core/api/catalog.php');

class catalog_renderer extends \plugin_renderer_base {

    /**
     * First enter to render
     *
     * @param array $trainings
     * @return string
     * @throws \moodle_exception
     */
    public function display() {

        // Add strings to JS.
        $this->page->requires->strings_for_js([
            'pleaserefresh',
        ], 'format_edadmin');
        $this->page->requires->strings_for_js([
            'training_found',
            'trainings_found',
            'no_training_found',
            'not_found',
            'filter_button',
            'filter_button_all',
        ], 'local_catalog');
        $this->page->requires->strings_for_js([
            'enrolmentpopuptitle',
            'next',
            'previous',
            'copylinktext',
            'copylinkerror',
            'notpermissionscourse',
            'trainingnotavailable',
        ], 'local_mentor_core');

        $jsparams = new \stdClass();
        $jsparams->collections = local_mentor_specialization_get_collections();

        $paramsrenderer = catalog_api::get_params_renderer();

        if ($paramsrenderer->trainingscount > 0) {
            // Init and call JS.
            $this->page->requires->js_call_amd(
                catalog_api::get_catalog_javascript('local_catalog/local_catalog'), 'init', $jsparams
            );
        }

        // Call template.
        return $this->render_from_template(catalog_api::get_catalog_template('local_catalog/catalog'), $paramsrenderer);
    }
}
