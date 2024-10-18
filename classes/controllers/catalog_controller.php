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
 * Catalog controller
 *
 * @package    local_catalog
 * @copyright  2021 Edunao SAS (contact@edunao.com)
 * @author     adrien <adrien@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog;

use local_mentor_specialization\custom_notifications_service;
use local_mentor_specialization\mentor_specialization;

defined('MOODLE_INTERNAL') || die();

use local_mentor_core;
use local_mentor_core\controller_base;

require_once($CFG->dirroot . '/local/mentor_core/classes/controllers/controller_base.php');
require_once($CFG->dirroot . '/local/mentor_core/api/session.php');
require_once($CFG->dirroot . '/local/mentor_core/api/catalog.php');

class catalog_controller extends controller_base {
    /**
     * Execute action
     *
     * @return mixed
     * @throws \moodle_exception
     */
    public function execute() {
        $action = $this->get_param('action');

        try {
            switch ($action) {
                case 'enrol_current_user':
                    $sessionid = $this->get_param('sessionid', PARAM_INT);
                    $enrolmentkey = $this->get_param('enrolmentkey', PARAM_RAW, null);
                    return $this->success(self::enrol_current_user($sessionid, $enrolmentkey));

                case 'get_session_enrolment_data':
                    $sessionid = $this->get_param('sessionid', PARAM_INT);
                    return $this->success(self::get_session_enrolment_data($sessionid));
                case 'get_user_collection_notifications':
                    $type = $this->get_param('type', PARAM_RAW);
                    return $this->success(self::get_user_collection_notifications($type));
                case 'set_user_notifications':
                    $notifications = $this->get_param('notifications');
                    $type = $this->get_param('type', PARAM_RAW);
                    return $this->success(self::set_user_notifications($notifications, $type));
                case 'get_all_collections':
                    return $this->success(self::get_all_collections());
                default:
                    break;
            }
        } catch (\moodle_exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Enrol the current user into a session
     *
     * @param int $sessionid
     * @param null|string $enrolmentkey optional default null
     * @return string url of the session course
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function enrol_current_user($sessionid, $enrolmentkey = null) {
        $session = local_mentor_core\session_api::get_session($sessionid);
        $result = $session->enrol_current_user($enrolmentkey);

        // Enrolment success, return the url of the session course.
        if ($result['status']) {
            return htmlspecialchars_decode($session->get_url()->out(), ENT_COMPAT);
        }

        if (isset($result['lang'])) {
            throw new \moodle_exception($result['lang'], 'local_catalog');
        }

        // Use the first warning.
        if (count($result['warnings']) > 1 || isset($result['warnings'][0])) {
            throw new \moodle_exception('errormoodle', 'local_catalog', '', $result['warnings'][0]['message']);
        }

        throw new \moodle_exception('errormoodle', 'local_catalog', '', $result['warnings']['message']);
    }

    /**
     * Get session enrolment data
     *
     * @param int $sessionid
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_session_enrolment_data($sessionid) {
        return local_mentor_core\session_api::get_session_enrolment_data($sessionid);
    }

    /**
     * Get user customized notifications
     *
     * @return array
     * @param string $type
     * @throws \moodle_exception
     */
    public static function get_user_collection_notifications($type) {
        if ($type != custom_notifications_service::$CATALOG_PAGE_TYPE) {
            throw new \moodle_exception( 'invaliddatausernotifpage', 'local_catalog');
        }
        return local_mentor_core\catalog_api::get_user_collection_notifications($type);
    }

    /**
     * Set user customized notifications
     *
     * @param string $notifications
     * @param string $type
     * @return string
     * @throws \moodle_exception
     */
    public static function set_user_notifications($notifications, $type) {
        $notifications = json_decode( $notifications, true);
        if (!is_array($notifications)) {
            throw new \moodle_exception('invaliddatausernotif', 'local_catalog');
        }
        if ($type != custom_notifications_service::$CATALOG_PAGE_TYPE) {
            throw new \moodle_exception('invaliddatausernotifpage', 'local_catalog');
        }
        
        return local_mentor_core\catalog_api::set_user_notifications($notifications, $type);
    }

    /**
     * Get mentor collections
     *
     * @return array
     */
    public static function get_all_collections() {
        return local_mentor_core\catalog_api::get_mentor_collections();
    }
    
}
