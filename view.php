<html>
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf8">
	<script src="http://code.jquery.com/jquery-1.8.3.js"></script>
	<script src="http://code.jquery.com/ui/1.10.0/jquery-ui.js"></script>
	<script src="jquery.jeditable.js"></script>

	<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css"/>
	<script src="ubikc_jeditable.js"></script>
	<style>
		.english,.partofspeech,.chinese,.word,.explanation,.questiontext,.choice,.answer,.tn{
			border: 1px solid black;
		}
	</style>

  </head>
  <body>


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
 * ubikc module main user interface
 *
 * @package    mod
 * @subpackage ubikc
 * @copyright  2012 HsiHo Huang  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/ubikc/locallib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->libdir .'/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);  // Course module ID
$f  = optional_param('f', 0, PARAM_INT);   // ubikc instance id

if ($f) {  // Two ways to specify the module
    $ubikc = $DB->get_record('ubikc', array('id'=>$f), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('ubikc', $ubikc->id, $ubikc->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('ubikc', $id, 0, false, MUST_EXIST);
    $ubikc = $DB->get_record('ubikc', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/ubikc:view', $context);

add_to_log($course->id, 'ubikc', 'view', 'view.php?id='.$cm->id, $ubikc->id, $cm->id);

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/ubikc/view.php', array('id' => $cm->id));

$PAGE->set_title($course->shortname.': '.$ubikc->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($ubikc);


$output = $PAGE->get_renderer('mod_ubikc');

echo $output->header();

echo $output->heading(format_string($ubikc->name), 2);

if (trim(strip_tags($ubikc->intro))) {
    echo $output->box_start('mod_introbox', 'pageintro');
    echo format_module_intro('ubikc', $ubikc, $cm->id);
    echo $output->box_end();
}

echo $output->box_start('generalbox ubikctree');
echo $output->ubikc_tree($ubikc, $cm, $course);
echo $output->box_end();


/******************************************************
 * Note: Below is an old version code, not maintained.
 *       It isn't good.
 */
function displayKC($tab,$ubikcid){
	include_once("mysql_connect.php");

	switch($tab){
		case 1:
			$sql = "SELECT id AS 'No.', english AS '英文', partofspeech AS 
						  '詞性', chinese AS '中文' FROM `mdl_ubikc_englishword` where `ubikcid` = $ubikcid";	
			$table_fields = array('mdl_ubikc_englishword','english','partofspeech','chinese');
			break;
		case 2:
			$sql = "SELECT id AS 'No.', word AS '名詞', explanation AS 
						  '解釋' FROM `mdl_ubikc_wordexplanation` where `ubikcid` = $ubikcid";	
			$table_fields = array('mdl_ubikc_wordexplanation','word','explanation');
			break;
		case 3: 
			$sql = "SELECT id AS 'No.', questiontext AS '題目', choice AS 
						  '選項', answer AS '解答' FROM `mdl_ubikc_questionbank`  where `ubikcid` = $ubikcid";	
			$table_fields = array('mdl_ubikc_questionbank','questiontext','choice', 'answer');
			break;
	}

		$result = mysql_query($sql);
		$total_fields = mysql_num_fields($result);
		$has_data = 0;
		
		for($r=0; $row = mysql_fetch_row($result); $r++){
			if($r==0){
				echo "<table class='tn' align='center' width='400'>";
				echo "<tr class='tn'>";// align='center'>";			
				for ($i = 1; $i < $total_fields; $i++)
					echo "<td class='tn'>" . mysql_field_name($result, $i) . "</td>";		
				echo "</tr>";		
				$has_data = 1;
			}
			echo "<tr>";		
			for($i = 1; $i < $total_fields; $i++){
				echo "<td class=$table_fields[$i] id='$table_fields[0]-$table_fields[$i]-$row[0]'>";echo "$row[$i]</td>";
			}
			echo "</tr>";		
		}		
		if(!$has_data)
			echo "目前沒有任何內容!!" ;
		else
			echo "</table>" ;
}

?>
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">英文單字</a></li>
    <li><a href="#tabs-2">名詞解釋</a></li>
    <li><a href="#tabs-3">題庫</a></li>
  </ul>
  
  <div id="tabs-1">
	<?php  displayKC(1,$ubikc->id);	?>
  </div>
  <div id="tabs-2">
    <?php  displayKC(2,$ubikc->id);	?>
  </div>
  <div id="tabs-3">
    <?php  displayKC(3,$ubikc->id);	?>
  </div>
</div>     

<?php


$sql2 = "SELECT `fullname` FROM `mdl_course` WHERE `category` = '$ubikc->course'";
$result2 = mysql_query($sql2);
$row2 = mysql_fetch_row($result2);
$sql3 = "SELECT `name` FROM `mdl_ubikc` WHERE `id` = '$ubikc->id'";
$result3 = mysql_query($sql3);
$row3 = mysql_fetch_row($result3);
$sql4 = "SELECT `examtime` FROM `mdl_ubikc` WHERE `id` = '$ubikc->id'";
$result4 = mysql_query($sql4);
$row4 = mysql_fetch_row($result4);


echo $row2[0].$row3[0].($row4[0]?"考試日期：".date('Y年m月d',$row4[0]):"");

if (has_capability('mod/ubikc:managefiles', $context)) {
    echo $output->container_start('mdl-align');
    echo $output->single_button(new moodle_url('/mod/ubikc/edit.php', array('id'=>$id)), get_string('edit'));
    echo $output->container_end();
}

echo $output->footer();
mysql_close($link);
?>

 </body>
</html>