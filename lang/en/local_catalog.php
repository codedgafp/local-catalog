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
 * Plugin strings
 *
 * @package    local
 * @subpackage catalog
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     remi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Catalogue de formation';

$string['catalogtitle'] = 'Offre de formation';
$string['training_found'] = 'formation trouvée';
$string['trainings_found'] = 'formations trouvées';
$string['no_training_found'] = 'Aucune formation trouvée';
$string['not_found'] = 'Aucun résultat';
$string['trainings_trainer'] = 'Proposée par';
$string['collection'] = 'Collection';
$string['entity'] = 'Structure Créatrice';
$string['in-progress'] = 'Embarquement immédiat';
$string['permanent-access'] = 'Accès permanent';
$string['reset'] = 'Réinitialiser les filtres';
$string['submit'] = 'Appliquer les filtres';
$string['search'] = 'Rechercher';
$string['filter_button'] = 'Filtrer';
$string['filter_button_all'] = 'Toutes';
$string['search_placeholder'] = 'Rechercher un contenu, une compétence, ...';
$string['enrolmentpopuptitle'] = 'Inscription à la session';
$string['certifying'] = 'Certifiante';
$string['thumbnail'] = 'Vignette';
$string['next'] = 'Suivant';
$string['previous'] = 'Précédent';
$string['no_trainings']
    = 'L’offre de formation arrivera bientôt. Les équipes formation sont à l’œuvre pour vous proposer des formations dès l’ouverture officielle de la plateforme Mentor. Vous pourrez alors consulter les fiches formation et vous inscrire aux sessions proposées.';

$string['enrolmessage'] = 'Confirmez-vous votre inscription à cette session ?';
$string['enrolmessagewithkey']
    = 'Pour confirmer votre inscription, merci de saisir la clé qui vous a été fournie. Dans le cas contraire, nous vous invitons à contacter les organisateurs.';

// Training Sheet.
$string['backcatalog'] = 'Retour vers le catalogue';
$string['contact'] = 'Contact : {$a}';
$string['objectivesandcontent'] = 'Objectifs et contenu de la formation';
$string['objectives'] = 'Objectifs';
$string['content'] = 'Contenu';
$string['prerequisite'] = 'Pré-requis';
$string['skills'] = 'Compétences';
$string['typicaljob'] = 'Métier(s)';
$string['termsoflicense'] = 'Termes de la licence';
$string['sessionsoffered'] = 'Sessions proposées';
$string['sessionlisting']
    = 'Retrouvez ci-dessous l’ensemble des sessions dans lesquelles vous pouvez vous inscrire en fonction des places disponibles.';
$string['logoof'] = 'Logo de {$a}';
$string['teaserof'] = 'Teaser de {$a}';
$string['producedby'] = 'Produite par {$a}';
$string['producerorganizationlogo'] = 'Logo de l\'organisme producteur';
$string['idsirh'] = 'Identifiant SIRH {$a}';
$string['readmore'] = 'Lire la suite';
$string['viewless'] = 'Voir moins';
$string['session'] = 'Session';
$string['sessions'] = 'Sessions';
$string['seeallsessions'] = 'Voir toutes les sessions';
$string['toconnect'] = 'Se connecter';
$string['registrationsession'] = 'Inscription à une session';
$string['nologginsessionaccess']
    = 'Pour s\'inscrire à une session de formation, il est nécessaire d\'être authentifié sur Mentor.';

// Sessions tile.
$string['permanentaccess'] = 'Accès permanent';
$string['ondate'] = 'Le {$a}';
$string['fromto'] = 'Du {$a->from} au {$a->to}';
$string['fromdate'] = 'A partir du {$a}';
$string['alreadyregistered'] = 'Déjà inscrit';
$string['placesavailable'] = '{$a} places disponibles';
$string['placeavailable'] = '{$a} place disponible';
$string['moredetails'] = 'Plus de détails';
$string['complete'] = 'Complet';

// Session Sheet.
$string['backtraining'] = 'Retour vers la fiche formation';
$string['inprogress'] = 'En cours';
$string['modality'] = 'Modalité : {$a}';
$string['access'] = 'Accéder';
$string['registration'] = 'Inscription';
$string['onlineduration'] = 'Durée en ligne {$a}';
$string['online'] = 'En ligne';
$string['presenceduration'] = 'Durée en présence {$a}';
$string['inpresence'] = 'En présence';
$string['targetaudience'] = 'Public cible';
$string['coaching'] = 'Accompagnement';
$string['locationsession'] = 'Lieu(x) du déroulement de la session';
$string['online'] = 'En ligne';
$string['presentiel'] = 'Présentiel';
$string['mixte'] = 'Présentiel et en ligne';
$string['openedregistration'] = 'Inscriptions ouvertes';
$string['complete'] = 'Complet';
$string['register'] = 'Inscription';
$string['location'] = 'Lieu(x) de formation';
$string['organizingstructure'] = 'Structure organisatrice';
$string['enrolmentkey'] = 'Clé d’inscription';
$string['sessionnotstarted'] = 'La session n\'a pas encore débuté.';
$string['courseaccesssoon'] = 'Vous êtes inscrit à la session de formation. Vous pourrez y accéder dès sa date d\'ouverture.';
$string['prerequisite'] = 'Pré-requis';

// Error.
$string['errorselfenrolment'] = 'L\'auto-inscription est désactivée';
$string['errorauthorizationselfenrolment'] = 'L\'auto-inscription ne vous est pas autorisée pour cette session.';
$string['errormoodle'] = '{$a}';
$string['notaccesstraining']
    = 'Cette formation ne semble pas disponible. Plusieurs raisons sont possibles :<br>
- Vous devez vous connecter sur Mentor au préalable.<br>
- Cette formation n’existe pas ou une erreur de saisie s’est glissée dans l’adresse.<br>
- Vous ne faites pas partie du public visé par cette formation.<br><br>
Pour plus d’informations, veuillez consulter la page Contact.';
$string['addtoexport'] = 'Sélectionner pour exporter le descriptif de la formation';
$string['removetoexport'] = 'Désélectionner pour supprimer le descriptif de la formation dans l\'export';
$string['toexport'] = 'Exporter';
$string['toexportbuttontitle'] = 'Exporter en PDF les descriptifs des formations sélectionnées';
$string['cancel'] = 'Annuler';
$string['exportpdfformat'] = 'Exporter au format PDF';
$string['selecttrainingtoexport'] = 'formation(s) sélectionnée(s) pour l\'export';
$string['pleaseselecttraining'] = 'Veuillez sélectionner au moins une formation à exporter.';
$string['nopermissions'] = 'Vous n’avez pas les les permissions requises pour accéder à cette page';
$string['invaliddatausernotif'] = 'Notifications utilisateurs: données invalides';
$string['invaliddatausernotifpage'] = 'Notifications utilisateurs: page non valide';

// Capabilities
$string['catalog:offeraccess'] = 'Donne accès à l\'offre de formation';


// Customized notifications
$string['usercollectionnotif:sucess'] = "Les notifications ont été mises à jour avec succès.";

$string['subscription_management_text'] = 'Vous serez informés chaque semaine par mél des nouvelles sessions disponibles dans une collection.';
