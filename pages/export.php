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
 * Export the selected trainings as PDF
 *
 * @package local_catalog
 * @copyright  2022 Edunao SAS (contact@edunao.com)
 * @author     adrien <adrien@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include config.php.
require_once('../../../config.php');

// Includes.
require_once($CFG->dirroot . '/local/catalog/classes/pdf_export.php');
require_once($CFG->dirroot . '/local/mentor_core/api/training.php');
require_once($CFG->dirroot . '/local/mentor_core/api/catalog.php');

// Increase time limit.
core_php_time_limit::raise();

// Increase memory limit.
raise_memory_limit(MEMORY_EXTRA);

require_login();

// Get entity.
$entityid = required_param('entityid', PARAM_INT);
$entity = \local_mentor_core\entity_api::get_entity($entityid);
$entity = $entity->get_main_entity();

// Check capabilities.
require_capability('local/entities:manageentity', $entity->get_context());

// Get entity trainings and sessions.
$sessions = $entity->get_available_sessions_to_catalog();

$trainings = [];
foreach ($sessions as $session) {
    if (!in_array($session->trainingid, $trainings)) {
        $trainings[] = $session->trainingid;
    }
}

// Create new PDF document.
$pdf = new \local_catalog\pdf_export(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set trainings.
$pdf->setTrainings($trainings);

// Add training pages.
$pdf->AddTrainingPages();

// Add the custom table of contents.
$pdf->AddTableOfContents();

// Close and output PDF document.
$pdf->Output('export_offre_formation_' . $entity->shortname . '_' . date('Y-m-d') . '.pdf', 'D');
