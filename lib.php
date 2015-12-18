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
 * Mandatory public API of ubikc module
 *
 * @package    mod
 * @subpackage ubikc
 * @copyright  2012 HsiHo Huang  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * List of features supported in ubikc module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function ubikc_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function ubikc_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function ubikc_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function ubikc_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function ubikc_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add ubikc instance.
 * @param object $data
 * @param object $mform
 * @return int new ubikc instance id
 */
function ubikc_add_instance($data, $mform) {
    global $DB;

	$data->timecreated = time();
	
    $cmid        = $data->coursemodule;
    $draftitemid = $data->files;

    $data->timemodified = time();
    
    $data->instanceid = $cmid;
    $data->id = $DB->insert_record('ubikc', $data);
    
    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    $context = get_context_instance(CONTEXT_MODULE, $cmid);
	

    if ($draftitemid) {	
        file_save_draft_area_files($draftitemid, $context->id, 'mod_ubikc', 'content', 0, array('subdirs'=>true));
    }
	
	ubikc_parse_file($cmid,$data->id);

    return $data->id;
}

/**
 * Update ubikc instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function ubikc_update_instance($data, $mform) {
    global $CFG, $DB;

    $cmid        = $data->coursemodule;
    $draftitemid = $data->files;

    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->kcrevision++;

    $DB->update_record('ubikc', $data);
    

    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    if ($draftitemid = file_get_submitted_draft_itemid('files')) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_ubikc', 'content', 0, array('subdirs'=>true));      
    }	

    ubikc_delete_original_data($data->id);
	ubikc_parse_file($cmid,$data->id);

    return true;
}

/**
 * Delete ubikc instance.
 * @param int $id
 * @return bool true
 */
function ubikc_delete_instance($id) {
    global $DB;

    if (!$ubikc = $DB->get_record('ubikc', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('ubikc', array('id'=>$ubikc->id));
	$DB->delete_records('ubikc_englishword', array('ubikcid'=>$ubikc->id));   
	$DB->delete_records('ubikc_questionbank', array('ubikcid'=>$ubikc->id));   
	$DB->delete_records('ubikc_wordexplanation', array('ubikcid'=>$ubikc->id));  

    return true;
}

/**
 * Delete original kc.
 * @param int $id
 * @return bool true
 */
function ubikc_delete_original_data($id) {
    global $DB;

    if (!$ubikc = $DB->get_record('ubikc', array('id'=>$id))) {
        return false;
    }

    $DB->delete_records('ubikc_englishword', array('ubikcid'=>$ubikc->id));   
    $DB->delete_records('ubikc_questionbank', array('ubikcid'=>$ubikc->id));   
    $DB->delete_records('ubikc_wordexplanation', array('ubikcid'=>$ubikc->id));  

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $ubikc
 * @return object|null
 */
function ubikc_user_outline($course, $user, $mod, $ubikc) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'ubikc',
                                              'action'=>'view', 'info'=>$ubikc->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;       

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $ubikc
 */
function ubikc_user_complete($course, $user, $mod, $ubikc) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'ubikc',
                                              'action'=>'view', 'info'=>$ubikc->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'ubikc');
    }
}

/**
 * Returns the users with data in one ubikc
 *
 * @todo: deprecated - to be deleted in 2.2
 *
 * @param int $ubikcid
 * @return bool false
 */
function ubikc_get_participants($ubikcid) {
    return false;
}

/**
 * Lists all browsable file areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function ubikc_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('ubikccontent', 'ubikc');

    return $areas;
}

/**
 * File browsing support for ubikc module content area.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function ubikc_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;


    if ($filearea === 'content') {
        if (!has_capability('mod/ubikc:view', $context)) {
            return NULL;
        }
        $fs = get_file_storage();

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        if (!$storedfile = $fs->get_file($context->id, 'mod_ubikc', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_ubikc', 'content', 0);
            } else {
                // not found
                return null;
            }
        }

        require_once("$CFG->dirroot/mod/ubikc/locallib.php");
        $urlbase = $CFG->wwwroot.'/pluginfile.php';

        // students may read files here
        $canwrite = has_capability('mod/ubikc:managefiles', $context);
        return new ubikc_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, $canwrite, false);
    }

    // note: ubikc_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the ubikc files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function ubikc_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/ubikc:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    array_shift($args); // ignore revision - designed to prevent caching problems only

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_ubikc/content/0/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
	
    // finally send the file
    // for ubikc module, we force download file all the time
    send_stored_file($file, 86400, 0, true);
}

/**
 * Parses uploaded file
 *
 * Note: It is an old version code, not maintained.
 *       It isn't good enough.
 * @return bool true
 */
 function ubikc_parse_file($instanceid,$ubikcid) {
    global $CFG;
    include_once("mysql_connect.php");
    $add_sql = "SELECT `contenthash`,`filesize`,`mimetype` FROM `mdl_files` WHERE contextid = (SELECT id FROM `mdl_context` WHERE `instanceid` = $instanceid) AND filename != '.' AND filesize != 0";
    $add_result = mysql_query($add_sql);

    while($row = mysql_fetch_row($add_result)){
        $delimiter = "";            
        if(strcmp($row[2],'text/csv')==0) $delimiter = ",";
        else if(strcmp($row[2],'text/plain')==0) $delimiter = "@";

        //if your OS system is windows, the "/" in $add_filepath should be modified to "\\".
        $add_filepath = $CFG->dataroot."/filedir/".substr($row[0],0,2)."/".substr($row[0],2,2)."/$row[0]";
        $fp =  fopen($add_filepath, "r");
        setlocale(LC_ALL, "zh_TW.UTF8");
        $elementNum = 0;
        $insert = "";
        $value = "";
        for($r=0; $add_contents = fgetcsv($fp, filesize($add_filepath)+1 ,$delimiter) ;$r++){
            if($r==0)   
                $insert = "INSERT INTO ".$CFG->dbname."`$add_contents[1]` ( `id`,`ubikcid`,";
            else if($r==1){
                $elementNum = count($add_contents);
                for($i=0; $i<$elementNum; $i++){
                    if($i == ($elementNum-1))  $insert .= "`".$add_contents[$i]."`)";
                    else $insert .= "`".$add_contents[$i]."`,";
                }
            }
            else{
                $tempNum = count($add_contents);
                if($tempNum != $elementNum ) echo "<script charset=utf8>alert('error: line ".($r+1)." !');</script>";                       
                $value = " VALUES ( NULL ,$ubikcid ,";
                for($i=0; $i<$elementNum; $i++){
                    if($i == ($elementNum-1)) $value .= "'".$add_contents[$i]."');";
                    else $value .= "'".$add_contents[$i]."',";
                }
                mysql_query($insert.$value);
            }
        }               
        fclose($fp);            
    }
    mysql_close(link);      
    return true;
 }

/**
 * This function extends the global navigation for the site.
 * It is important to note that you should not rely on PAGE objects within this
 * body of code as there is no guarantee that during an AJAX request they are
 * available
 *
 * @param navigation_node $navigation The ubikc node within the global navigation
 * @param stdClass $course The course object returned from the DB
 * @param stdClass $module The module object returned from the DB
 * @param stdClass $cm The course module instance returned from the DB
 */
function ubikc_extend_navigation($navigation, $course, $module, $cm) {
    /**
     * This is currently just a stub so that it can be easily expanded upon.
     * When expanding just remove this comment and the line below and then add
     * you content.
     */
    $navigation->nodetype = navigation_node::NODETYPE_LEAF;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function ubikc_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-ubikc-*'=>get_string('page-mod-ubikc-x', 'ubikc'));
    return $module_pagetype;
}

/**
 * Export ubikc resource contents
 *
 * @return array of file content
 */
function ubikc_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = array();
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $ubikc = $DB->get_record('ubikc', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ubikc', 'content', 0, 'sortorder DESC, id ASC', false);

    foreach ($files as $fileinfo) {
        $file = array();
        $file['type'] = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_ubikc/content/'.$ubikc->kcrevision.$fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $contents[] = $file;
    }

    return $contents;
}