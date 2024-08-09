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
 * Catalog main page
 *
 * @package    local
 * @subpackage catalog
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__DIR__, 2) . '/config.php');
require_once($CFG->dirroot . '/local/catalog/lib.php');
require_once($CFG->dirroot . '/course/format/edadmin/lib.php');
require_once($CFG->dirroot . '/local/mentor_core/api/training.php');
require_once($CFG->dirroot . '/local/mentor_core/api/session.php');
require_once($CFG->dirroot . '/local/mentor_specialization/lib.php');

// Redirect to user profile when not completed required fields.
if (isloggedin() && !profile_has_required_custom_fields_set($USER->id)) {
    redirect($CFG->wwwroot . '/user/edit.php?id=' . $USER->id . '&amp;course=' . SITEID);
}

$trainingid = optional_param('trainingid', null, PARAM_INT);

// Redirect to the training sheet page.
if (!is_null($trainingid)) {
    redirect($CFG->wwwroot . '/local/catalog/pages/training.php?trainingid=' . $trainingid);
}

$context = context_system::instance();

$site = get_site();

if (isloggedin() && !has_capability('local/catalog:offeraccess', $context)) {
    redirect($CFG->wwwroot, get_string('nopermissions', 'local_catalog'));
    exit;
}

// Settings first element page.
$PAGE->set_url('/local/catalog/index.php');
$PAGE->set_context($context);
$PAGE->set_title($site->fullname . ' : ' . new lang_string('catalogtitle', 'local_catalog'));
$PAGE->set_pagelayout('standard');

// Set navbar.
$PAGE->navbar->add(new lang_string('catalogtitle', 'local_catalog'));

// Call renderer.
$renderer = $PAGE->get_renderer('local_catalog', 'catalog');

$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

// Require strings for js.
$PAGE->requires->strings_for_js(['confirm', 'cancel'], 'moodle');
$PAGE->requires->strings_for_js(
    [
        'enrolmentpopuptitle',
        'addtoexport',
        'removetoexport',
        'toexport',
        'exportpdfformat',
    ]
    , 'local_catalog'
);

// Setting header page.
$PAGE->set_heading(new lang_string('catalogtitle', 'local_catalog'));
echo $OUTPUT->header();
echo $OUTPUT->skip_link_target();

// Displays renderer content.
echo $renderer->display();

// Display footer.
echo $OUTPUT->footer();
