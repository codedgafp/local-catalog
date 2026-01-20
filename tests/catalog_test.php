<?php

namespace local_catalog;

defined('MOODLE_INTERNAL') || die();

use core\exception\moodle_exception;
use local_catalog\helper\test_helper;
use local_mentor_core\session;
use local_mentor_core\session_api;
use local_mentor_core\training;
use stdClass;

/**
 * Test access to catalog depending on capabilities.
 *
 * @package    local_catalog
 * @copyright  2018 University of Nottingham
 * @author     Neill Magill <neill.magill@nottingham.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalog_test extends \advanced_testcase
{
    /**
     * Test that users without the necessary capability are redirected.
     */
    public function test_redirect_without_capability(): void
    {
        global $CFG, $DB, $USER;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $context = \context_system::instance();
        $externalUser = $DB->get_record('role', array('shortname' => 'utilisateurexterne'), '*', MUST_EXIST);
        role_assign($externalUser->id, $user->id, $context->id);
        $this->setUser($user);
        assign_capability('local/catalog:offeraccess', CAP_PROHIBIT, $externalUser->id, $context->id);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Unsupported redirect detected, script execution terminated');

        require($CFG->dirroot . '/local/catalog/index.php');
    }

    public function test_page_training_redirect(): void
    {
        self::resetAfterTest();
        self::setAdminUser();

        $trainingparams['name'] = 'name';
        $trainingparams['shortname'] = 'shortname';
        $trainingparams['content'] = 'content';
        $trainingparams['status'] = training::STATUS_DRAFT;
        $trainingparams['entityname'] = 'New Entity 1';
        $trainingparams['entityshortname'] = 'New Entity 1';
        $training = test_helper::create_training($trainingparams);
        $session = session_api::create_session($training->id, 'Session test', true);

        $session->status = session::STATUS_IN_PROGRESS;
        session_api::update_session($session);

        $data = new stdClass();
        $data->opento = session::OPEN_TO_CURRENT_ENTITY;
        $session->update($data);

        $user = self::getDataGenerator()->create_user();
        $session->get_entity()->add_member($user);

        $params['userid'] = $user->id;
        $params['trainingid'] = $training->id;
        $redirect = test_helper::page_training_redirect($params);

        self::assertFalse($redirect);

        $data = new stdClass();
        $data->opento = session::OPEN_TO_NOT_VISIBLE;
        $session->update($data);

        $redirect = test_helper::page_training_redirect($params);

        self::assertTrue($redirect);
    }
}
