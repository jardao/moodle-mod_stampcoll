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
 * This file defines the main Stamp collection module setting form
 *
 * @package    mod_stampcoll
 * @copyright  2008 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/lib/filelib.php');

/**
 * Stamp collection module setting form
 */
class mod_stampcoll_mod_form extends moodleform_mod {

    /**
     * Defines the form
     */
    public function definition() {
        global $COURSE, $CFG;

        $mform = $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor(false);
        }

        // Stamp collection.
        $mform->addElement('header', 'stampcollection', get_string('modulename', 'stampcoll'));

        // Stamp image.
        $imageoptions = array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => array('image'),
            'maxbytes' => $COURSE->maxbytes, 'return_types' => FILE_INTERNAL);
        $mform->addElement('filemanager', 'image', get_string('stampimage', 'stampcoll'), null, $imageoptions);
        $mform->addHelpButton('image', 'stampimage', 'stampcoll');

        // Display users with no stamps.
        $mform->addElement('selectyesno', 'displayzero', get_string('displayzero', 'stampcoll'));
        $mform->setDefault('displayzero', 0);

        // Common module settings.
        $this->standard_coursemodule_elements();


        // @mfernandriu modifications
        // Grade options
        $mform->addElement('header', 'gradeheading', get_string('grade'));

        $mform->addElement('text', 'grademaxgrade', get_string('modgrademaxgrade', 'grades'));
        $mform->addRule('grademaxgrade', get_string('grademaxgradeerror', 'stampcoll'), 'regex', '/^(0*[1-9][0-9]*(\.[0-9]+)?|0+\.[0-9]*[1-9][0-9]*)$/', 'client');

        $mform->addElement('text', 'pointsperstamp', get_string('pointsperstamp', 'stampcoll'));
        $mform->addHelpButton('pointsperstamp', 'pointsperstamp', 'stampcoll');
        $mform->addRule('pointsperstamp', get_string('pointsperstamperror', 'stampcoll'), 'regex', '/^(0*[1-9][0-9]*(\.[0-9]+)?|0+\.[0-9]*[1-9][0-9]*)$/', 'client');

        if ($this->_features->gradecat) {
            $mform->addElement(
                'select', 'gradecat',
                get_string('gradecategoryonmodform', 'grades'),
                grade_get_categories_menu($COURSE->id, $this->_outcomesused)
            );
            $mform->addHelpButton('gradecat', 'gradecategoryonmodform', 'grades');
        }


        // Buttons.
        $this->add_action_buttons();
    }

    /**
     * Sets the default form data
     *
     * When editing an existing instance, this method copies the current stamp image into the
     * draft area (standard filemanager workflow).
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $COURSE;

        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('image');
            $options = array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => array('image'),
                'maxbytes' => $COURSE->maxbytes, 'return_types' => FILE_INTERNAL);
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_stampcoll', 'image', 0, $options);
            $defaultvalues['image'] = $draftitemid;
        }


        //@mfernandriu modifictations
        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionstampsenabled']=
            !empty($default_values['completionstamps']) ? 1 : 0;
        if(empty($default_values['completionstamps'])) {
            $default_values['completionstamps']=1;
        }
    }


    // @mfernandriu modifications
    /**
    * Add elements for setting the custom completion rules.
    *
    * @category completion
    * @return array List of added element names, or names of wrapping group elements.
    */
    public function add_completion_rules() {

    $mform = $this->_form;

        $group = [
            $mform->createElement('checkbox', 'completionstampsenabled', ' ', get_string('completionstamps', 'moodleoverflow')),
            $mform->createElement('text', 'completionstamps', ' ', ['size' => 3]),
        ];
        $mform->setType('completionstamps', PARAM_INT);
        $mform->addGroup($group, 'completionstampsgroup', get_string('completionstampsgroup','moodleoverflow'), [' '], false);
        $mform->addHelpButton('completionstampsgroup', 'completionstamps', 'moodleoverflow');
        $mform->disabledIf('completionstamps', 'completionstampsenabled', 'notchecked');

        return ['completionstampsgroup'];
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionstampsenabled']) && $data['completionstamps'] != 0);
    }


    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked
            $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionstampsenabled) || !$autocompletion) {
               $data->completionstamps = 0;
            }
        }
        return $data;
    }
}
