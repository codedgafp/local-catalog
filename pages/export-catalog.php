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
 * Export the selected catalog trainings as PDF
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
require_once($CFG->dirroot . '/local/mentor_core/classes/database_interface.php');

// Increase time limit.
core_php_time_limit::raise();

// Increase memory limit.
raise_memory_limit(MEMORY_EXTRA);

$selectedtrainingsid = required_param_array('trainingsid', PARAM_INT);
$trainingsid = [];

// Check if training has available session.
foreach ($selectedtrainingsid as $trainingid) {
    $training = \local_mentor_core\training_api::get_training($trainingid);
    if ($training->is_available_to_user()) {
        // Add trainings to export.
        $trainingsid[] = $trainingid;
    }
}

// Create new PDF document.
$pdf = new \local_catalog\pdf_export(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set trainings.
$pdf->setTrainings($trainingsid);

// Add training pages.
$pdf->AddTrainingPages(false);

// Get entity manager role data.
$dbi = \local_mentor_core\database_interface::get_instance();
$admindedie = $dbi->get_role_by_name('admindedie');

// Add introduction.
$pdf->AddIntro();

// Close and output PDF document.
$pdf->Output('export_offre_formation_' . date('Y-m-d') . '.pdf', 'D');
