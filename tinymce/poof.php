<?php

// This file is not a part of Moodle - http://moodle.org/
// This is a none core contributed module.
//
// This is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License
// can be see at <http://www.gnu.org/licenses/>.

/*
 * __________________________________________________________________________
 *
 * PoodLL TinyMCE for Moodle 2.x
 *
 * This plugin need to use together with Poodll filter.
 *
 * @package    poof
 * @subpackage tinymce_poof
 * @copyright  2013 UC Regents
 * @copyright  2013 Justin Hunt  {@link http://www.poof.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * __________________________________________________________________________
 */

define('NO_MOODLE_COOKIES', false);

require('../../../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/lib/editor/tinymce/plugins/poof/tinymce/poof.php');

if (isset($SESSION->lang)) {
    // Language is set via page url param.
    $lang = $SESSION->lang;
} else {
    $lang = 'en';
}

$editor = get_texteditor('tinymce');
$plugin = $editor->get_plugin('poof');
$itemid = optional_param('itemid', '', PARAM_TEXT);
$cmid = optional_param('cmid', '', PARAM_TEXT);
$courseid = optional_param('courseid', '', PARAM_TEXT);
$areahtml = optional_param('areahtml', '', PARAM_RAW);

//require_login(null,null,$cmid);  
require_login();

//massage it a bit
$areahtml = rawurldecode($areahtml);

//contextid
$usercontextid=context_user::instance($USER->id)->id;

global $DB;

//get courseid
if(!$courseid){
	$coursemodule = $DB->get_record('course_modules', array('id'=>$cmid), '*', IGNORE_MISSING );
	if($coursemodule){
		$courseid = $coursemodule->course;
	}
}


$instruction = get_string('pressinsert', 'tinymce_poof');


$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('title', 'tinymce_poof'));
//$PAGE->set_heading('');
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/lib/editor/tinymce/plugins/poof/tinymce/css/poof.css'));
$PAGE->requires->js(new moodle_url($editor->get_tinymce_base_url() . 'tiny_mce_popup.js'),true);
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/lib/editor/tinymce/plugins/poof/tinymce/js/poof.js'),true);
//$PAGE->requires->js(new moodle_url($CFG->wwwroot. '/filter/poof/module.js'),true);

$textarea =get_string('followingfiles', 'tinymce_poof');

echo $OUTPUT->header();
?>
<div style="text-align: center;">
<p id="messageAlert"><?php echo $instruction; ?></p>

</div>
<form>
   <div>
	 <?php 
		  $newareahtml = $areahtml; 
		  $newareahtml = replaceLegacyFiles($newareahtml, $usercontextid, $itemid,$textarea);
		  $newareahtml = replacePoodLLFiles($newareahtml, $usercontextid, $itemid, $courseid,$textarea);
		  echo "<textarea rows=\"20\" cols=\"50\">"  . $textarea . "</textarea><br />";

	 ?>
      <input type="hidden" name="contextid" value= "<?php echo $usercontextid;?>" id="context_id" />
      <input type="hidden" name= "wwwroot" value="<?php echo $CFG->wwwroot;?>" id="wwwroot" />
      <input type="button" id="insert" name="insert" value="{#insert}" onclick="tinymce_poof_Dialog.insert('<?php echo rawurlencode($newareahtml); ?>');" />  
      <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
      <input type="hidden" name="action" value="download">
   </div>
</form>
<?php
	echo $OUTPUT->footer();

	function replaceLegacyFiles($newareahtml, $usercontextid, $itemid, &$textarea){
		global $CFG;
		
		 $regexp = "\/file.php\/([^\"]*)\"";
		  if(preg_match_all("/$regexp/siU", $newareahtml, $matches, PREG_SET_ORDER)) {
			foreach($matches as $match) {
				//make a real URL out of download url
				$download_url = $CFG->wwwroot . '/file.php/' . $match[1];
				//$textarea .= "du:" . $download_url . "&#13;&#10;";	
			//filename
			$filename = array_shift(explode('?', basename($download_url)));
			 //$textarea .= "fn:" . $filename . "&#13;&#10;";
			 //do the file swap and fetch the new url
			 $newurl = legacy_to_draft('/' . $match[1], $filename, $usercontextid, $itemid);
			 //$textarea .= "nu:" . $newurl . "&#13;&#10;";
			  //if its ok, report back
			  if($newurl){
				//$textarea .= "na:" . strlen($newareahtml) . "&#13;&#10;";
				$newareahtml = str_replace($download_url,$newurl,$newareahtml);
				//$textarea .= "na:" . strlen($newareahtml) . "&#13;&#10;";
				$textarea .= "File:" . $filename . "&#13;&#10;";
			  }
			}//end of foreach
		  }//end of if pregmatchall
		  return $newareahtml;
	
	}
	
		function replacePoodLLFiles($newareahtml, $usercontextid, $itemid, $courseid, &$textarea){
		global $COURSE,$CFG, $DB;
		
			//check if we have a courseid, if not cancel out
			if(!$courseid){
				$courseid = $COURSE->id;
				if(!$courseid || $courseid < 2){
					$textarea .= "(skipping PoodLL files ...)" . "&#13;&#10;";	
					return $newareahtml;
				}
			}
		
			$search = '/{POODLL:.*?}/is';
			$matches = null;
			if(preg_match_all($search, $newareahtml, $matches,PREG_PATTERN_ORDER)){
			//print_r($matches);
			//return false;
				if (!$matches) {
					// error or not filtered
					//echo "no poodll";
					return $newareahtml;
				}

				foreach($matches[0] as $match) {
					$filterprops = fetch_poodll_filter_properties($match);
					switch($filterprops['type']){
						case 'audio':
						case 'video':
								if ($filterprops['protocol']=='legacy' || $filterprops['protocol']=='rtmp'){
									$relativepath = '/' . $courseid . '/' . $filterprops['path'];
									$download_url = $CFG->wwwroot . '/file.php' . $relativepath;
									$filename = array_shift(explode('?', basename($download_url)));

									//do the file swap and fetch the new url
									 $newurl = legacy_to_draft($relativepath, $filename, $usercontextid, $itemid);
									  //if its ok, report back
									  if($newurl){
										$textarea .= "PoodLL file:" . $filename . "&#13;&#10;";	
										$displayfilename = $filename;
										//if this is flv in audio we want to force an audio player
										if ($filterprops['type']=='audio'){
											$displayfilename = str_replace('.flv','.audio.flv',$filename);
										}
										$newurl = "<a href=\"" . $newurl . "\" >". $displayfilename . "<a/>";
										$newareahtml = str_replace($match,$newurl,$newareahtml);										
									}//end of if newurl
								}//end of if protocol

						default:
						
					}
				}
			}else{
				return $newareahtml;
			}//end of if pregmatch
			return $newareahtml;
	
	}
	
	function legacy_to_draft($relativepath, $filename, $contextid, $itemid){
		global $CFG, $USER, $DB;


		//create the file record for our new file
		 $file_record = new stdClass();
		 $file_record->userid    = $USER->id;
		 $file_record->contextid = $contextid;
		 $file_record->component = 'user';//$component;
		 $file_record->filearea = 'draft';//$filearea;
		 $file_record->itemid   = $itemid;
		 $file_record->filepath = '/';
		 $file_record->filename = $filename;
		 $file_record->license  = $CFG->sitedefaultlicense;
		 $file_record->author   = fullname($USER);
		 $file_record->source    = '';
		 $file_record->timecreated = time(); 
		 $file_record->timemodified= time();

		// extract relative path components
		$args = explode('/', ltrim($relativepath, '/'));

		if (count($args) == 0) { // always at least courseid, may search for index.html in course root
			print_error('invalidarguments');
		}

		$courseid = (int)array_shift($args);
		$relativepath = implode('/', $args);

		// security: limit access to existing course subdirectories
		$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

		//prepare to fetch file
		$context = context_course::instance($course->id);
		$fs = get_file_storage();
		$fullpath = "/$context->id/course/legacy/0/$relativepath";
		
		//get the legacy file as a stored file
		if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
			if (strrpos($fullpath, '/') !== strlen($fullpath) -1 ) {
				$fullpath .= '/';
			}
			if (!$file = $fs->get_file_by_hash(sha1($fullpath.'/.'))) {
			   return false;// send_file_not_found();
			}
		}
		
		//first check the draft file is not already there
		//if file already exists, just return the replace url without creating the file
		if($fs->file_exists($file_record->contextid ,$file_record->component,
					$file_record->filearea ,$file_record->itemid ,
					$file_record->filepath,$file_record->filename)){
			$ret =  moodle_url::make_draftfile_url($itemid, "/", $filename)->out(false);
		}else{
			$sf = $fs->create_file_from_storedfile($file_record, $file);
			if($sf){
				$ret = moodle_url::make_draftfile_url($itemid, "/", $filename)->out(false);
			}else{

				$ret = false;
			}//end of if $sf
		}//end of if file exists
		return $ret;
		
	}//end of legacy to draft
	
	function fetch_poodll_filter_properties($filterstring){
	//this just removes the {POODLL: .. } to leave us with the good stuff.	
	//there MUST be a better way than this.
	$rawproperties = explode ("{POODLL:", $filterstring);
	$rawproperties = $rawproperties[1];
	$rawproperties = explode ("}", $rawproperties);	
	$rawproperties = $rawproperties[0];

	//Now we just have our properties string
	//Lets run our regular expression over them
	//string should be property=value,property=value
	//got this regexp from http://stackoverflow.com/questions/168171/regular-expression-for-parsing-name-value-pairs
	$regexpression='/([^=,]*)=("[^"]*"|[^,"]*)/';
	$matches; 	

	//here we match the filter string and split into name array (matches[1]) and value array (matches[2])
	//we then add those to a name value array.
	$itemprops = array();
	if (preg_match_all($regexpression, $rawproperties,$matches,PREG_PATTERN_ORDER)){		
		$propscount = count($matches[1]);
		for ($cnt =0; $cnt < $propscount; $cnt++){
			// echo $matches[1][$cnt] . "=" . $matches[2][$cnt] . " ";
			$itemprops[$matches[1][$cnt]]=$matches[2][$cnt];
		}
	}

	return $itemprops;

}
	
	
	
	
	
	
	
	function xlegacy_to_draft($download_url, $contextid, $itemid){
		global $CFG, $USER;
		
		
		//get file manipulator
		$fs = get_file_storage();
		$filename = array_shift(explode('?', basename($download_url)));

		//create the file record for our new file
		 $file_record = new stdClass();
		 $file_record->userid    = $USER->id;
		 $file_record->contextid = $contextid;
		 $file_record->component = 'user';//$component;
		 $file_record->filearea = 'draft';//$filearea;
		 $file_record->itemid   = $itemid;
		 $file_record->filepath = '/';
		 $file_record->filename = $filename;
		 $file_record->license  = $CFG->sitedefaultlicense;
		 $file_record->author   = fullname($USER);
		 $file_record->source    = '';
		 $file_record->timecreated = time(); 
		 $file_record->timemodified= time();
		 
		//set up our download options
		$options = array();
		//$options['headers']=array('Cookie', 'MoodleSession=4mme532jq528uaj6ie3bno6pc6; MOODLEID1_=9%25EB%25F7s%25BD');
		$options['postdata']=null;
		$options['fullresponse']=false;
		$options['timeout']=300;
		$options['connecttimeout']=20;
		$options['skipcertverify']=false;
		$options['calctimeout']=false;
		
		//first check the file is not already there
		//if file already exists, just return the replace url without creating the file
		if($fs->file_exists($file_record->contextid ,$file_record->component,
					$file_record->filearea ,$file_record->itemid ,
					$file_record->filepath,$file_record->filename)){
			$ret =  moodle_url::make_draftfile_url($itemid, "/", $filename)->out(false);
		}else{
			$sf = $fs->create_file_from_url($file_record, $download_url,$options, true);
			if($sf){
				$ret = moodle_url::make_draftfile_url($itemid, "/", $filename)->out(false);
			}else{
				$ret = false;
			}//end of if $sf
		}//end of if file exists
		
		return $ret;
	
	}//end of legacy_to_draft