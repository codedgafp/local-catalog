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
 * Training catalog renderer
 *
 * @package    local_catalog
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog\output;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/catalog/lib.php');

class training_renderer extends \plugin_renderer_base {

    /**
     * First enter to render
     *
     * @param \stdClass $training
     * @param \stdClass[] $sessions
     * @return string
     * @throws \moodle_exception
     */
    public function display($training, $sessions) {
        global $CFG;

        global $SESSION, $PAGE;

        // Systematic redirection to the training sheet after login in case of redirection after login to subscribe to a session.
        // Set wantsurl to the training sheet.
        if(!isloggedin() && !isguestuser()){
            $SESSION->wantsurl = new moodle_url($PAGE->url);
        }

        // Get all collections.
        $collectionsnames = local_mentor_specialization_get_collections();
        $collectionscolors = local_mentor_specialization_get_collections('color');

        // Build collection tiles.
        $training->collectiontiles = [];
        foreach (explode(',', $training->collection) as $collection) {
            // If a collection is missing, we skip.
            if (!isset($collectionsnames[$collection])) {
                continue;
            }

            $tile = new \stdClass();
            $tile->name = $collectionsnames[$collection];
            $tile->color = $collectionscolors[$collection];
            $training->collectiontiles[] = $tile;
        }

        $training->hasproducerorganization = false;

        if (!empty($training->producingorganizationlogo) || !empty($training->producingorganization) || !empty
            ($training->contactproducerorganization)) {
            $training->hasproducerorganization = true;
        }

        // Check training has session.
        $training->hassession = !empty($sessions);

        // For single session to page.
        if (count($sessions) === 1) {
            // Get single session.
            $training->session = current($sessions);
            $training->hasonesession = true;

            // Is not training preview.
            if ($training->session->id !== 0) {
                // If user enroll.
                if (
                    $training->session->isenrol &&
                    (
                        $training->session->status !== \local_mentor_core\session::STATUS_OPENED_REGISTRATION ||
                        $training->session->istutor
                    )
                ) {
                    $training->session->hasaccess = true;
                } else {
                    // Get enrol data.
                    $enrolmentdata = \local_mentor_core\session_api::get_session_enrolment_data($training->session->id);

                    if ($enrolmentdata->selfenrolment) {
                        // Simple enroll.
                        $training->session->isselfenrolment = true;

                        // With key.
                        $training->session->hasselfregistrationkey = $enrolmentdata->hasselfregistrationkey;
                    } else {
                        // Special enroll.
                        $training->session->isotherenrolement = true;

                        // With message.
                        $training->session->message = $enrolmentdata->message;
                    }
                }
            }

            $training->unenrolurl = new moodle_url("/enrol/self/unenrolself.php", [
                'enrolid' => $this->get_enrol_id($training->session->id)
            ]);
        } else {
            foreach($sessions as $key => $session) {
                $unsubscribeurl = new moodle_url("/enrol/self/unenrolself.php", [
                    'enrolid' => $this->get_enrol_id($session->id)
                ]);
                $sessions[$key]->unenrolurl = $unsubscribeurl->out();
            }

            // Set sessions data.
            $training->sessions = $sessions;
        }

        $training->available_sessions = json_encode($sessions);
        $training->navbar = $this->create_navbar_data($training);

        // Init and call JS.
        $this->page->requires->strings_for_js([
            'cancel',
        ], 'format_edadmin');
        $this->page->requires->strings_for_js([
            'register',
            'unsubscribe',
            'access',
            'toconnect',
            'nologginsessionaccess',
            'registrationsession',
        ], 'local_catalog');
        $this->page->requires->strings_for_js([
            'copylinktext',
            'copylinkerror',
            'enrolmentpopuptitle',
        ], 'local_mentor_core');

        // JS init.
        $this->page->requires->js_call_amd('local_catalog/training_catalog', 'init');

        // Call template.
        return $this->render_from_template(\local_mentor_core\catalog_api::get_training_template('local_catalog/training'),
            $training);
    }

    /**
     * Get the enrol id depends of the session id
     * 
     * @param int $sessionid
     * 
     * @return int
     */
    private function get_enrol_id(int $sessionid): int
    {
        global $DB;

        $sql = "SELECT e.id
                FROM {enrol} e
                INNER JOIN {course} c ON e.courseid = c.id
                INNER JOIN {session} s ON c.shortname = s.courseshortname
                WHERE s.id = :sessionid
                AND e.enrol = 'self'
                ";

        $params["sessionid"] = $sessionid;

        return $DB->get_field_sql($sql, $params);
    }

    /**
     * Not Access notification render
     *
     * @return string
     * @throws \moodle_exception
     */
    public function not_access($message) {
        \core\notification::error($message);

        return '';
    }


    /**
     * Create data for navbar template.
     *
     * @param \stdClass $training
     * @return \stdClass
     * @throws \coding_exception
     */
    public function create_navbar_data($training) {
        $navbar = new \stdClass();
        $navbar->uniqid = uniqid();
        $navbar->items = [];

        $data = new \stdClass();
        $data->key = 'content';
        $data->id = 'nav-' . $data->key;
        $data->label = get_string('content', 'local_catalog');
        $data->content = $training->content;
        $data->active = true;
        $navbar->items[] = $data;

        $optionaldata = ['prerequisite', 'skills', 'typicaljob'];

        foreach ($optionaldata as $optionaldatum) {
            if (!isset($training->{$optionaldatum}) || empty($training->{$optionaldatum})) {
                continue;
            }

            $data = new \stdClass();
            $data->key = $optionaldatum;
            $data->id = 'nav-' . $data->key;
            $data->label = get_string($optionaldatum, 'local_catalog');
            $data->content = ensureParagraphTag($training->{$optionaldatum});
            $navbar->items[] = $data;
        }

        $data = new \stdClass();
        $data->key = 'termsoflicense';
        $data->id = 'nav-' . $data->key;
        $data->label = get_string('termsoflicense', 'local_catalog');
        $data->content = ensureParagraphTag($training->licensetermsfullname);
        $navbar->items[] = $data;

        return $navbar;
    }
}
