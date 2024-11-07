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
 * PDF export class
 *
 * @package    local_catalog
 * @copyright  2022 Edunao SAS (contact@edunao.com)
 * @author     adrien <adrien@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog;

use local_mentor_core\session;
use local_mentor_core\session_api;
use local_mentor_specialization\mentor_training;

require_once($CFG->libdir . '/tcpdf/tcpdf.php');
require_once($CFG->dirroot . '/local/mentor_specialization/lib.php');

class pdf_export extends \TCPDF {

    public $collections   = [];
    public $trainings     = [];
    public $trainingPages = [];
    public $tableofcontentlines;

    /**
     * Override PDF constructor
     *
     * @param $orientation
     * @param $unit
     * @param $format
     * @param $unicode
     * @param $encoding
     * @param $diskcache
     * @param $pdfa
     * @throws \moodle_exception
     */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8',
        $diskcache = false, $pdfa = false) {
        global $CFG;
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

        // Set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('mentor.gouv.fr');
        $this->SetTitle('Offre de formation Mentor');

        // Add Marianne font.
        $fontpath = $CFG->libdir . '/tcpdf/fonts/marianne.php';

        if (!file_exists($fontpath)) {
            throw new \moodle_exception('File not found : ' . $fontpath);
        }

        // Set font styles.
        $this->AddFont('Marianne', '', $fontpath);
        $this->SetFont('Marianne', '', 10);
        $this->SetTextColor(30, 30, 30);
        $this->setHtmlLinksStyle([0, 0, 145], '');

        // Set margins.
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(0);

        // Set auto page breaks.
        $this->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // Set image scale factor.
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->setImageScale(1.53);
    }

    /**
     * Set export trainings and collections
     *
     * @param int[] $trainings
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function setTrainings($trainings) {

        foreach ($trainings as $trainingid) {
            $training = \local_mentor_core\training_api::get_training($trainingid);
            $this->trainings[$trainingid] = $training;

            // Get trainings collections.
            $collectionsImploded = $training->get_collections();
            $collections = explode(';', $collectionsImploded);

            foreach ($collections as $collection) {
                if (!isset($this->collections[$collection])) {
                    $this->collections[$collection] = [];
                    $this->tableofcontentlines++;
                }
                $this->collections[$collection][$training->name] = $trainingid;
                $this->tableofcontentlines++;
            }
        }

        // Sort collection trainings.
        foreach ($this->collections as $collection => $trainings) {
            // Sort collection trainings by name.
            uksort($trainings, function($a, $b) {
                return strcmp(local_mentor_core_sanitize_string($a), local_mentor_core_sanitize_string($b));
            });
            $this->collections[$collection] = $trainings;
        }

        // Sort collections by name.
        ksort($this->collections);

        // Sort trainings by libelle.
        uasort($this->trainings, function($a, $b) {
            return strcmp(local_mentor_core_sanitize_string($a->name), local_mentor_core_sanitize_string($b->name));
        });
    }

    /**
     * Add training pages
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function AddTrainingPages($summary = true) {
        $startpage = 2;

        if ($summary) {
            // Calculate the number of pages used for the table of contents.
            $summarypages = ceil(($this->tableofcontentlines + 16) / 34);

            $startpage = $summarypages + 1;
        }

        // Add empty pages for table of contents.
        for ($i = 1; $i <= $startpage; $i++) {

            // First training page.
            if ($i == $startpage) {
                $this->setPrintHeader(false);
                $this->SetY(0);
            }

            if ($i > 1 && $i != $startpage) {
                $this->SetMargins(PDF_MARGIN_LEFT, 55, PDF_MARGIN_RIGHT);
            }
            $this->AddPage();
        }

        $this->setPage($startpage);

        // Hide header and footer.
        $this->setPrintHeader(false);

        $counttrainings = count($this->trainings);
        $index = 0;

        $alreadyprintedtrainings = [];

        // Add training pages.
        foreach ($this->collections as $collectionname => $trainings) {

            foreach ($trainings as $trainingname => $trainingid) {

                // Check if the training has already been printed.
                if (in_array($trainingid, $alreadyprintedtrainings)) {
                    continue;
                }

                $training = $this->trainings[$trainingid];

                // Add the training page.
                $this->AddTrainingPage($training);
                $index++;

                // Add an empty next page.
                if ($index != $counttrainings) {
                    $this->AddPage();
                }
                $alreadyprintedtrainings[] = $training->id;
            }
        }
    }

    /**
     * Override PDF Header
     *
     * @return void
     */
    public function Header() {
        global $CFG;

        $this->setY(15);
        $this->setX(0);
        $this->SetMargins(0, 42, 0);
        $this->SetFont('Marianne');

        // Logo.
        $logorepublique = $CFG->dirroot . '/local/catalog/pix/logo_republique.png';
        $logomentor = $CFG->dirroot . '/local/catalog/pix/logo_mentor.png';
        $this->Image($logorepublique, 10, 8, 30, '', 'PNG', '', 'T', true, 500, '', false, false, 0, false, false, false);

        // Background.
        $this->setFillColor(245, 245, 254);
        $this->MultiCell(0, 42, '', 0, 'J', true, 0, 0, 0);

        $this->SetTextColor(30, 30, 30);

        $this->setX(50);

        // Mentor logo and texts.
        $this->Image($logomentor, '', 9, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->writeHTMLCell('', '', 50, 20, '<h1 style="font-size: 15pt;">Offre de formation Mentor</h1>');
        $this->writeHTMLCell('', '', 50, 29, '<div style="font-size: 10pt;">En date du ' . date('d/m/Y') . '</div>');
    }

    /**
     * Override PDF footer
     *
     * @return void
     */
    public function Footer() {
        // Position at 15 mm from bottom.
        $this->SetY(-15);
        $this->SetRightMargin(0);

        $this->SetFont('Marianne', '', 9);

        // Page number.
        $this->Cell(0, 10, $this->getAliasNumPage(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    /**
     * Define PDF intro
     *
     * @return void
     */
    public function AddIntro() {
        $this->setPage(1);
        $tagvs = array(
            'br' => [['h' => 0.1]],
        );
        $this->setHtmlVSpace($tagvs);
        $this->setCellHeightRatio(1.4);

        $html = '<p>Les éléments des pages suivantes sont extraits de l\'offre de formation de la plateforme interministérielle Mentor en date du '.date("d/m/Y").' .</p>
 
        <p>Cette offre évolue en continu avec des contributions de toute la communauté interministérielle.</p>
      
        <p>Pour plus de compléments, n\'hésitez pas à contacter le référent Mentor de votre administration <a href="https://mentor.gouv.fr/local/staticpage/view.php?page=contact">(page de contact)</a>.</p>';
        $this->setY(48);
        $this->writeHTMLCell(150, '', '', $this->getY(), $html, 0, 1);
    }

    /**
     * Display the table of contents
     *
     * @return void
     */
    public function AddTableOfContents() {

        $tagvs = array(
            'br' => [['h' => 0.1]],
        );
        $this->setHtmlVSpace($tagvs);

        $html = '';

        foreach ($this->collections as $collectionname => $trainings) {
            // Add a new collection table of content.
            $html .= '<h4>' . $collectionname . '</h4>';
            $html .= '<table style="margin-top:0; padding-top:0; padding-left:12px;width:100%;"><tbody>';
            foreach ($trainings as $trainingname => $trainingid) {

                $trainingindex = $this->trainingPages[$trainingid];

                $html .= '<tr>';

                $link = '<a href="#' . $trainingindex . '">' . $trainingname . '</a>';
                $html .= '<td>' . $link . '</td>';

                $link = '<a href="#' . $trainingindex . '">' . $trainingindex . '</a>';

                $html .= '<td style="vertical-align: bottom;">' . $link . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table><br/>';
        }

        $this->setPage(1);
        $this->setX(15);
        $this->setY(46);

        // Add the into on the main page.
        $this->AddIntro();

        // Add the table of contents.
        $this->writeHTMLCell(300, '', '', $this->getY() + 10, '<h3>SOMMAIRE</h3>', 0, 1);
        $this->writeHTMLCell(340, '', '', $this->getY() + 5, $html, 0, 1);
    }

    /**
     * Display a training page
     *
     * @param int|mentor_training $training
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function AddTrainingPage($training) {
        global $CFG;

        if (is_int($training)) {
            $training = $this->trainings($training);
        }

        $this->SetTextColor(30, 30, 30);
        $this->setPrintHeader(false);
        $this->setCellHeightRatio(1.4);
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->setListIndentWidth(8);

        // Add a page link for the pagination and table of contents.
        $indexlink = $this->AddLink();
        $this->SetLink($indexlink, 0, '*' . $this->PageNo());

        // Index the training page.
        $this->trainingPages[$training->id] = $this->PageNo();

        // Set background color.
        $this->SetFillColor(255, 255, 255);

        $this->setY(18);

        // Training name.
        $trainingnamey = $this->getY();
        $this->writeHTMLCell(140, '', '', $trainingnamey, '<h1 style="font-size: 15pt;">' . $training->name . '</h1>', 0, 1);

        $trainingnameyafter = $this->getY();

        // Certifying tag.
        if ($training->certifying == 1) {
            $this->SetTextColor(169, 70, 69);

            $this->Image($CFG->dirroot . '/local/catalog/pix/group.png', 170, $trainingnamey + 3, 4, '');

            $this->writeHTMLCell(56, '', 140, $trainingnamey + 3, '<div>Certifiante</div>',
                0, 0,
                false, true, 'R');
        }

        $tagvs = array(
            'p' => array(1 => array('n' => 1, 'h' => 0)),
            'br' => array(0 => array('n' => 0.3, 'h' => 0), 1 => array('n' => 0, 'h' => 0)),
        );
        $this->setHtmlVSpace($tagvs);

        $this->setY($trainingnameyafter + 3);

        // Collections.
        $collectiony = $this->getY();
        $collections = $training->get_collections(',&nbsp;');
        $this->SetTextColor(110, 60, 90);
        $this->writeHTMLCell(110, '', '', $collectiony, '<div>' . $collections . '</div>', 0, 1);
        $aftercollectiony = $this->getY();

        // ID sirh.
        if ($training->idsirh && $training->idsirh != '') {
            $this->SetTextColor(110, 60, 90);
            $this->writeHTMLCell(72, '', $this->getX() + 110, $collectiony,
                '<div>Identifiant SIRH <b>' . $training->idsirh . '</b></div>',
                0, 1, false, true,
                'R');

            $collectiony = $this->getY();
        }

        // Training link.
        $this->SetTextColor(110, 60, 90);
        $this->writeHTMLCell(80, '', 117, $collectiony,
            '<a href="' . $CFG->wwwroot . '/catalog/' . $training->id . '">https://mentor.gouv.fr/catalog/' . $training->id .
            '</a>',
            0, 1,
            false, true, 'R');

        $mainentityy = max($aftercollectiony, $this->getY()) + 5;

        // Training main entity.
        $this->SetTextColor(30, 30, 30);
        $this->writeHTMLCell(130, '', '', $mainentityy,
            '<p>Proposée par <b>' . $training->get_entity()->get_main_entity()->name . '</b></p>', 0, 1);

        // Teaser or Thumbnail.
        if ($thumbnail = $training->get_training_picture('teaserpicture')) {
            $data = base64_encode($thumbnail->get_content());
            $img = base64_decode(preg_replace('#^data:image/[^;]+;base64,#', '', $data));
            $this->Image("@" . $img, 155, $mainentityy, 40, 20);

        } else if ($thumbnail = $training->get_training_picture()) {
            $data = base64_encode($thumbnail->get_content());
            $img = base64_decode(preg_replace('#^data:image/[^;]+;base64,#', '', $data));
            $this->Image("@" . $img, 155, $mainentityy, 40, 20);
        }

        // Producer.
        $producerorganizationlogo = $training->get_training_picture('producerorganizationlogo');

        if ($producerorganizationlogo || !empty($training->producingorganization) ||
            !empty($training->contactproducerorganization)) {

            $table = '<table cellpadding="10" style="font-size: 9pt;">';
            $table .= '<tbody>';
            $table .= '<tr>';

            // Producer logo.
            $table .= '<td style="width: 80px;vertical-align: middle;line-height: 50%;">';
            if ($producerorganizationlogo) {
                $img_base64_encoded = 'data:image/png;base64,' . base64_encode($producerorganizationlogo->get_content());
                $table .= '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) .
                          '" width="60px">';
            }
            $table .= '</td>';

            $table .= '<td align="left" style="width: 420px;">';

            // Producer name.
            if (!empty($training->producingorganization)) {
                $table .= 'Produite par <b>'
                          . $training->producingorganization . '</b><br/>';
            }

            // Producer contact.
            if (!empty($training->contactproducerorganization)) {
                $table .= 'Contact : ' . $training->contactproducerorganization;
            }

            $table .= '</td>';
            $table .= '</tr>';
            $table .= '</tbody>';
            $table .= '</table>';

            $this->SetLineStyle(array('color' => array(229, 229, 229)));
            $this->writeHTMLCell(120, '', '', $this->getY() + 5, $table, 1, 1, false, true, 'L', false);
        } else {
            $this->SetY($this->getY() + 10);
        }

        // Catch phrase.
        $this->setY($this->getY() + 6);
        $this->writeHTMLCell(160, '', '', '', $training->catchphrase, 0, 1);

        // Training goal.
        $tagvs = array(
            'p' => array(1 => array('n' => 1, 'h' => 0)),
            'br' => array(0 => array('n' => 0.5, 'h' => 0), 1 => array('n' => 0, 'h' => 0)),
        );
        $this->setHtmlVSpace($tagvs);

        // Clean empty tags at the end of the string.
        $traininggoal = local_mentor_core_clean_html($training->traininggoal);

        $this->writeHTMLCell(160, '', '', $this->getY() + 3, $traininggoal, 0, true, false, true, 'L', false);

        // Sessions.
        // Get all available sessions.
        $sessions = $training->get_sessions('sessionpermanent DESC, startdate ASC');
        $availablesessions = [];
        foreach ($sessions as $session) {

            $allowedstatus = [session::STATUS_OPENED_REGISTRATION, session::STATUS_IN_PROGRESS];
            if (!in_array($session->status, $allowedstatus)) {
                continue;
            }
            $availablesessions[] = $session;
        }

        if (count($availablesessions) > 0) {
            $this->writeHTMLCell(160, '', '', $this->getY() + 5, '<h3 style="font-size: 11pt;">Sessions : </h3>', 0, true, false,
                true,
                'L', false);

            $html = '<ul>';

            $displayedsessions = 0;
            foreach ($availablesessions as $session) {

                $html .= '<li>';

                // Set Date Time Zone at France.
                $dtz = new \DateTimeZone('Europe/Paris');

                // Set session start and end date.
                if (!empty($session->sessionstartdate) && !$session->sessionpermanent) {
                    $sessionstartdate = $session->sessionstartdate;
                    $startdate = new \DateTime("@$sessionstartdate");
                    $startdate->setTimezone($dtz);
                    $html .= $startdate->format('d/m/Y');
                } else {
                    $html .= 'Accès permanent';
                }

                $html .= ' - ';

                // Duration.
                if ($session->onlinesessionestimatedtime > 0 && $session->presencesessionestimatedtime == 0) {
                    $html .= 'Durée en ligne ' . local_mentor_core_minutes_to_hours($session->onlinesessionestimatedtime);
                } else if ($session->onlinesessionestimatedtime == 0 && $session->presencesessionestimatedtime > 0) {
                    $html .= 'Durée en présence ' . local_mentor_core_minutes_to_hours($session->presencesessionestimatedtime);
                } else {
                    $html .= 'En ligne ' . local_mentor_core_minutes_to_hours($session->onlinesessionestimatedtime) .
                             ' - En présence ' . local_mentor_core_minutes_to_hours($session->presencesessionestimatedtime);
                }

                // Public cible.
                if (!empty($session->publiccible)) {
                    $html .= ' - ';
                    $html .= 'Public cible : ' . $session->publiccible;
                }

                // Is session full.
                $availableplaces = $session->get_available_places();

                if ($availableplaces !== '' && $availableplaces <= 0) {
                    $html .= '<span style="color: #666;"> (Complet)</span>';
                }

                $html .= '</li>';

                $displayedsessions++;

                if ($displayedsessions == 3) {
                    break;
                }
            }

            $html .= '</ul>';
            $this->writeHTMLCell(175, '', '', $this->getY(), $html, 0, true, false, true, 'L', false);
        }

        // Prérequis.
        if ($training->prerequisite != '') {
            $this->writeHTMLCell(160, '', '', $this->getY() + 5, '<h3 style="font-size: 11pt;">Prérequis : </h3>', 0, true, false,
                true,
                'L', false);
            $this->writeHTMLCell(160, '', '', $this->getY(), '<p>' . $training->prerequisite . '</p>', 0, true, false, true, 'L',
                false);
        }

        // Métiers.
        if ($training->typicaljob != '') {
            $this->writeHTMLCell(160, '', '', $this->getY() + 5, '<h3 style="font-size: 11pt;">Métier(s) : </h3>', 0, true, false,
                true, 'L', false);
            $this->writeHTMLCell(160, '', '', $this->getY(), '<p>' . $training->typicaljob . '</p>', 0, true, false, true, 'L',
                false);
        }
    }
}
