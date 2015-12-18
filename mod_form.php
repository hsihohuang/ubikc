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
 * ubikc configuration form
 *
 * This file is used when adding/editing a module to a course. 
 * It contains the elements that will be displayed on the form responsible 
 * for creating/installing an instance of the module. 
 *
 * @package    mod
 * @subpackage ubikc
 * @copyright  2012 HsiHo Huang  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_ubikc_mod_form extends moodleform_mod {
    function definition() {
        global $CFG;
        $mform = $this->_form;

        $config = get_config('ubikc');

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags))
            $mform->setType('name', PARAM_TEXT);
        else
            $mform->setType('name', PARAM_CLEANHTML);
       
        $mform->addRule('name', null, 'required', null, 'client');
        $this->add_intro_editor($config->requiremodintro);
		
		$mform->addElement('date_selector', 'timeavailable', get_string('kcavailable', 'ubikc'),array('optional' => true));
        $mform->addElement('date_selector', 'timedue', get_string('kcdue', 'ubikc'),array('optional' => true));        
        $mform->addElement('date_selector', 'examtime', get_string('examtime', 'ubikc'),array('optional' => true));		
        $mform->addElement('header', 'content', get_string('contentheader', 'ubikc'));
        $mform->addElement('filemanager', 'files', get_string('files'), null, array('subdirs'=>1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL));              
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        //set the revision number = 1
        $mform->addElement('hidden', 'kcrevision');
        $mform->setType('kcrevision', PARAM_INT);
        $mform->setDefault('kcrevision', 1);
    }

    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            // editing existing instance - copy existing files into draft area
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_ubikc', 'content', 0, array('subdirs'=>true));
            $default_values['files'] = $draftitemid;               
        }        
    }
}