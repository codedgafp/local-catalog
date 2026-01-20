<?php

namespace local_catalog\helper;

use local_mentor_core\session_api;
use local_mentor_core\training;
use local_mentor_core\training_api;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class test_helper
{
    /**
     * Simulate the training.php page redirection part
     *
     * @param array $params ['userid', 'trainingid']
     * @return boolean
     */
    public static function page_training_redirect(array $params): bool
    {
        $userid = $params['userid'];
        $trainingid = $params['trainingid'];

        $useravailablesessions = session_api::get_user_available_sessions($userid);
        $availablesessions = array_filter($useravailablesessions, function ($session) use ($trainingid) {
            return $session->trainingid == $trainingid;
        });

        return empty($availablesessions);
    }

    /**
     * Create a training object
     *
     * @param array $params
     * @return training
     */
    public static function create_training(array $params): training
    {
        $data = new stdClass();
        $data->name = $params['name'];
        $data->shortname = $params['shortname'];
        $data->content = $params['content'];
        $data->status = $params['status'];

        $entityid = \local_mentor_core\entity_api::create_entity([
            'name' => $params['entityname'],
            'shortname' => $params['entityshortname'],
        ]);
        $entity = \local_mentor_core\entity_api::get_entity($entityid);
        $formationid = $entity->get_entity_formation_category();
        $data->categorychildid = $formationid;

        $data->categoryid = $entity->id;
        $data->creativestructure = $entity->id;

        return training_api::create_training($data);
    }
}
