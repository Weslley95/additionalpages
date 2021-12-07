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
 * Local plugin "additional pages"
 *
 * @package    local_additionalpages
 * @copyright  2021 Weslley <weslleybezerra95@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../course/renderer.php');

/**
 * Count method enrolment self
 * 
 * @return int
 */
function check_enrolment_self() {
    global $DB;
    
    // Get courses method enrol 'sefl' = true
    $sql = "SELECT * FROM {enrol} AS e WHERE e.status = 0 and e.enrol = 'self';";
    
    $enrolment = $DB->get_records_sql($sql);
    
    return count($enrolment);
}

/**
 * Get id name custom field course 'origem'
 * @param 
 * @return int
 */
function get_id_customfield_origem() {
    global $DB;
    
    // Get id custom field course 'origem'
    $fieldid = $DB->get_field('customfield_field', 'id', array('shortname' => 'origem'));
    
    return $fieldid;
}

/**
 * Count method enrolment self course per country available
 * @param int $userd id user
 * @return int course available for the users country
 */
function check_country_course($countryuser) {
    global $DB;
    
    // Id custom field course 'origim'
    $customfieldid = get_id_customfield_origem();
    
    $sql = "SELECT cd.instanceid 
            FROM {customfield_data} AS cd
            INNER JOIN {enrol} AS e ON e.courseid = cd.instanceid and e.enrol = 'self'
            WHERE cd.fieldid = $customfieldid and e.status = 0 and cd.intvalue = $countryuser;";
    
    // Get courses for country
    $countrycourse = $DB->get_records_sql($sql);
    
    return count($countrycourse);
}

/**
 * Count method enrolment self
 * @param int $userd id user
 * @return int
 */
function check_country_user($userid) {
    global $DB;
    
    // Get country user
    $countryuser = $DB->get_field('user', 'country', array('id' => $userid));
    
    // Replace string for number
    switch ($countryuser) {
        
        case 'AR':
            $countryid = 1;
            break;
        
        case 'BR':
            $countryid = 2;
            break;
        
        case 'CL':
            $countryid = 3;
            break;
        
        case 'PE':
            $countryid = 4;
            break;
        
        default:
            continue;
    }
    
    return $countryid;
}

/**
 * Get record courses method enrol 'sefl' = true
 * @param int Id user
 * @return mixed object courses
 */
function consult_courses_method_self($userid) {
    global $DB;
    
    // Country user
    $coutryid = check_country_user($userid);
    
    if($coutryid > 0) {
        
        // Get id custom field course 'origem'
        $fieldid = get_id_customfield_origem();
        
        /**
        * Get courses method enrol 'sefl' = true
        * Country user-> cd.value (1-> AR, 2->BR)
        */
        $sql = "SELECT c.*, cc.name AS categoryname 
                FROM {course} AS c
                INNER JOIN {enrol} AS e ON e.courseid = c.id
                INNER JOIN {course_categories} AS cc ON cc.id = c.category
                INNER JOIN {customfield_data} AS cd ON cd.instanceid = e.courseid 
                   and cd.fieldid = $fieldid and cd.value = $coutryid
                WHERE e.enrol = 'self' and status = false and c.id
                NOT IN (
                   SELECT e.courseid FROM {enrol} AS e
                   INNER JOIN {user_enrolments} AS ue ON ue.enrolid = e.id
                   WHERE ue.userid = $userid and e.status = false and e.enrol = 'self'
                ) 
                ORDER BY c.fullname ASC;";
        $courses = $DB->get_records_sql($sql);
    } else {
        $courses = 0;
    }
    
    return $courses;
}

/**
 * Count courses method enrol 'sefl' = true
 * @param object user
 * @return int $countcourses
 */
function count_courses_method_self($userid) {
    
    $countcourses = consult_courses_method_self($userid);
   
    return count($countcourses);
}


/**
 * Get courses method enrol 'sefl' = true
 *
 * @param int Id user
 * 
 * @return mixed object courses
 */
function get_all_courses_method_self($userid) {
    
    $courses = consult_courses_method_self($userid);
    
    return $courses;
}

/**
 * Creat table for courses self enrolments
 *
 * @param int Id user
 * 
 * @return mixed object courses
 */
function get_table_courses_data($courses) {
    
    global $OUTPUT, $CFG;
    
    $table = new html_table();
    $table->head  = array(
        get_string('course'),
        get_string('category'),
        get_string('summary'),
        get_string('enrolment', 'local_additionalpages')
        );

    $table->align = array('left', 'left', 'center', 'center');
    $table->width = '100%';
    $table->data  = array();

    foreach ($courses as $course) {

        // Get img course
        $course = new core_course_list_element($course);

        // Icon and name course
        $content = $OUTPUT->action_icon(null, new pix_icon('i/courseevent', get_string('course')));
        $content .= $course->fullname;
        
        // Category course
        $category = $course->categoryname; 
        
        // Display course summary
        $chelper = new coursecat_helper();          
        $summary = html_writer::start_tag('div', array('class' => 'icon fa fa-sign-in fa-search',
            'id' => "course-popover-{$course->id}", 'role' => 'button', 'data-region' => 'popover-region-toggle',
            'data-toggle' => 'popover', 'data-placement' => 'left',
            'data-content' => $chelper->get_course_formatted_summary($course, ['noclean' => true, 'para' => false]),
            'data-html' => 'true', 'tabindex' => '0', 'data-trigger' => 'focus'));
        $summary .= html_writer::end_tag('div'); // End summary.
        
        // Get link enrol subscription
        $urlenrol = new moodle_url('/enrol/index.php?', array('id'=>$course->id));
        $enrolsefl = $OUTPUT->action_icon($urlenrol, new pix_icon('i/settings', get_string('enrolme', 'core_enrol'), 'core', array('class' => 'icon fa fa-sign-in fa-fw')));

        // Add a row to the table
        $table->data[] = array(
            $content,
            $category,
            $summary,
            $enrolsefl
        );
    }
    
    return $table;
}
