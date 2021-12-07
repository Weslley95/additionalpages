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
 * Local plugin "additional pages" - Language pack
 *
 * @package    local_additionalpages
 * @copyright  2021 Weslley <weslleybezerra95@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot.'/local/mr/bootstrap.php');

global $CFG;

$userid = $USER->id;

$PAGE->set_url('/local/additionalpages/subscription.php', array('id' => $userid));

$site = get_site();

if (!empty($CFG->forceloginforprofiles)) {
    require_login();
    if (isguestuser()) {
        $PAGE->set_context(context_system::instance());
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('guestcantaccessprofiles', 'error'),
                              get_login_url(),
                              $CFG->wwwroot);
        echo $OUTPUT->footer();
        die;
    }
} else if (!empty($CFG->forcelogin)) {
    require_login();
}

$context = context_user::instance($userid, MUST_EXIST);
$heading = get_string('selfenrolment', 'local_additionalpages');

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('titletable', 'local_additionalpages'));
$PAGE->set_heading($heading);
echo $OUTPUT->header($heading);
echo $OUTPUT->skip_link_target();

// Country user
$countryuser = check_country_user($userid);

// Get courses
$courses = get_all_courses_method_self($userid);

if(check_enrolment_self() > 0 || !empty($courses)) {
    if(count_courses_method_self($userid) > 0 && check_country_course($countryuser) > 0){
        // Table
        $o = $OUTPUT->heading(get_string('titletable', 'local_additionalpages'));
        $o .= $OUTPUT->box_start();
        $o .= html_writer::table(get_table_courses_data($courses));
        $o .= $OUTPUT->box_end();
    } else if(check_country_course($countryuser) == 0 && check_enrolment_self() > 0) {
        $o = $OUTPUT->heading(get_string('countryenrolment', 'local_additionalpages'));
    } else {
        $o = $OUTPUT->heading(get_string('limitenrolment', 'local_additionalpages'));
    }
} else {
    $o = $OUTPUT->heading(get_string('statusenrolment', 'local_additionalpages'));
}

echo $o;
echo $OUTPUT->footer();
