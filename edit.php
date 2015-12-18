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
 * Manage files in ubikc module instance
 *
 * @package    mod
 * @subpackage ubikc
 * @copyright  2012 HsiHo Huang 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/ubikc/locallib.php");
require_once("$CFG->dirroot/mod/ubikc/lib.php");
require_once("$CFG->dirroot/mod/ubikc/edit_form.php");
require_once("$CFG->dirroot/repository/lib.php");

$id = required_param('id', PARAM_INT);  // Course module ID

// get the information about what is going to be edited 
$cm = get_coursemodule_from_id('ubikc', $id, 0, false, MUST_EXIST);//course module abbreviated 'cm'
$context = get_context_instance(CONTEXT_MODULE, $cm->id, MUST_EXIST);
$ubikc = $DB->get_record('ubikc', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

//verifies that user is logged in and has the capability before editing
require_login($course, false, $cm);
require_capability('mod/ubikc:managefiles', $context);

add_to_log($course->id, 'ubikc', 'edit', 'edit.php?id='.$cm->id, $ubikc->id, $cm->id);

$PAGE->set_url('/mod/ubikc/edit.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname.': '.$ubikc->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($ubikc);

//prepare the elements so that they can correctly display existing attached files
$data = new stdClass();
$data->id = $cm->id;
$options = array('mainfile'=>true, 'subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL);
file_prepare_standard_filemanager($data, 'files', $options, $context, 'mod_ubikc', 'content', 0);

//define an array of options for each file-handling form element
$mform = new mod_ubikc_edit_form(null, array('data'=>$data, 'options'=>$options));//form definition code is in edit_form.php

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/ubikc/view.php', array('id'=>$cm->id)));

} else if ($formdata = $mform->get_data()) {
	//Handling submitted data
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $context, 'mod_ubikc', 'content', 0);
    $DB->set_field('ubikc', 'kcrevision', $ubikc->kcrevision+1, array('id'=>$ubikc->id));
    	
	ubikc_delete_original_data($ubikc->id);
	ubikc_parse_file($data->id,$ubikc->id);   
    
    redirect(new moodle_url('/mod/ubikc/view.php', array('id'=>$cm->id)));    
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox ubikctree');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();