<?php

namespace local_catalog;
defined('MOODLE_INTERNAL') || die();
use core_role\privacy\provider;

use function PHPUnit\Framework\isEmpty;

/**
 * Test access to catalog depending on capabilities.
 *
 * @package    local_catalog
 * @copyright  2018 University of Nottingham
 * @author     Neill Magill <neill.magill@nottingham.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalog_test extends \advanced_testcase {

    /**
     * Test that users without the necessary capability are redirected.
     */
    public function test_redirect_without_capability() {
        global $CFG, $USER, $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
      
        $context = \context_system::instance();
        $externalUser = $DB->get_record('role', array('shortname' => 'utilisateurexterne'), '*', MUST_EXIST);
        role_assign($externalUser->id, $user->id, $context->id);
        $this->setUser($user);
        assign_capability('local/catalog:offeraccess', CAP_PROHIBIT, $externalUser->id, $context->id);
    
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Unsupported redirect detected, script execution terminated');

        require($CFG->dirroot . '/local/catalog/index.php');
    }
}
