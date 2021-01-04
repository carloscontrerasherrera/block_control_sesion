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
 * Control session block.
 *
 * @package    block_control_sesion
 * @copyright  2020 onwards Carlos Contreras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/control_sesion/lib.php');
require_once($CFG->dirroot.'/blocks/control_sesion/block_control_sesion_filtro_form.php');
require_once($CFG->libdir.'/excellib.class.php');
global $CFG, $DB, $USER, $OUTPUT, $PAGE, $COURSE;
// Visualización de datos.
// Check for all required variables.
$iduser = 0;
$idcourseid = required_param('c', PARAM_INT);
$instance = required_param('id', PARAM_INT);
$viewpage = optional_param('viewpage', false, PARAM_BOOL);
$config = block_control_sesion_get_config($instance);
$ini = $config->ini;
$interval = $config->interval;
$reporttype = optional_param('t', 0, PARAM_INT);
$detailed = optional_param('a', false, PARAM_BOOL);
$download = optional_param('d', false, PARAM_BOOL);
$iduser = optional_param('u', $USER->id, PARAM_INT);
$month = optional_param('m', (new DateTime("now"))->format('m'), PARAM_INT);
$year = optional_param('y', (new DateTime("now"))->format('Y'), PARAM_INT);
$idcourse = optional_param('cc', block_control_sesion_fix_courseyear(new DateTime("now")), PARAM_INT);
$f = optional_param('f', "now", PARAM_TEXT);
$ff = optional_param('ff', "now", PARAM_TEXT);
$group = optional_param('g', 0, PARAM_INT);
$date = new DateTime($f);
$datefin = new DateTime($ff);
$tipofiltro = $reporttype;
$groupfiltro = $group;
if (!$c = $DB->get_record('course', array('id' => $idcourseid))) {
    print_error('invalidcourse', 'control_sesion', $idcourseid);
}
require_login($c);
$context = context_course::instance($COURSE->id);
$showsessions = has_capability('block/control_sesion:allsessions', $context);
$mysession = has_capability('block/control_sesion:mysession', $context);
if ($iduser == 0 && !$showsessions) {
    $iduser = $USER->id;
}
if ($iduser == 0 || $iduser != $USER->id || !$config->visibleus) {
    $PAGE->set_heading(get_string('infoallsessions', 'block_control_sesion'));
    require_capability('block/control_sesion:allsessions', context_course::instance($idcourseid));
} else {
    $PAGE->set_heading(get_string('infosession', 'block_control_sesion'));
}
$PAGE->set_url('/blocks/control_sesion/view.php', array('c' => $idcourseid, 'id' => $instance));
$PAGE->set_pagelayout('base');
$settingsnode = $PAGE->settingsnav->add("Sesiones");
$urltoday = new moodle_url('/blocks/control_sesion/view.php', array('c' => $idcourseid, 'id' => $instance));
$editnode = $settingsnode->add("Sesiones", $urltoday);
$editnode->make_active();
$filtro = new block_control_sesion_filtro_form();
$toform['id'] = $instance;
$toform['c'] = $idcourseid;
$toform['u'] = $iduser;
$toform['t'] = $reporttype;
$toform['a'] = $detailed;
$toform['user'] = $iduser;
$toform['date'] = strtotime($f);
$toform['date_fin'] = strtotime($ff);
$toform['month'] = $month;
$toform['year'] = $year;
$toform['course'] = $idcourse;
$toform['group'] = $group;
$filtro->set_data($toform);
$res = array();
for ($x = 0; $x <= 5; $x++) {
    $res[$x] = "btn-secondary";
}
$res[$reporttype] = "btn-info";
switch ($reporttype) {
    case 3:
    case 5:
        $totaldays = 30;
        break;
    case 4:
        $totaldays = date ("z", strtotime((new DateTime('2020-12-31'))->format('Y-m-d')));
        break;
    default:
        $totaldays = 1;
}
if ($fromform = $filtro->get_data()) {
    $month = $fromform->month;
    $year = $fromform->year;
    $idcourse = $fromform->course;
    $iduser = $fromform->user;
    $group = $fromform->group;
    $groupfiltro = $group;
    $f = new DateTime("now");
    $ff = new DateTime("now");
    if ($reporttype < 3) {
        $f->setTimeStamp($fromform->date);
    }
    if ($reporttype < 3) {
        $ff->setTimeStamp($fromform->date_fin);
    }
    if ($showsessions && ($reporttype == 0 || $reporttype == 3 || $reporttype == 4)) {
        $iduser = 0;
    }
    $date = $f;
    $datefin = $ff;
}
$results = block_control_sesion_users_list($instance, $iduser, $detailed, $reporttype, $date->format('Y-m-d'), $totaldays, $month,
                                $year, $idcourse, $group, $datefin->format('Y-m-d'));
if ($download) {
    block_control_sesion_download_data($results);
    return;
}
$urlbase = array('c' => $idcourseid, 'id' => $instance, "g" => $group, "m" => $month, "y" => $year, "cc" => $idcourse,
                "f" => $date->format('Y-m-d'), "u" => $iduser, "ff" => $datefin->format('Y-m-d'));
$urltable = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => 0, 't' => 0, 'a' => true, "d" => false)));
$urluser = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => $iduser, 't' => 1, 'a' => true, "d" => false)));
$urlweekly = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => $iduser, 't' => 2, 'a' => true, "d" => false)));
$urlmonthly = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => 0, 't' => 3, 'a' => true, "d" => false)));
$urlannual = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => 0, 't' => 4, 'a' => true, "d" => false)));
$urldetailmonth = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => $iduser, 't' => 5, 'a' => true, "d" => false)));
$urldownload = new moodle_url('/blocks/control_sesion/view.php',
                            array_merge($urlbase, array('u' => 0, 't' => $reporttype, "d" => true)));
echo $OUTPUT->header();
echo html_writer::start_tag('div', array("class" => "btn-group", "id" => "group_cabecera"));
echo html_writer::start_tag('a', array('href' => $urltable));
echo "<button class='btn-group form-control ".$res[0]."'>".get_string('summary', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a')."";
echo html_writer::start_tag('a', array('href' => $urluser));
echo "<button class='btn-group form-control ".$res[1]."'>".get_string('detailed', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array('href' => $urlweekly));
echo "<button class='btn-group form-control ".$res[2]."'>".get_string('for_days', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array('href' => $urlmonthly));
echo "<button class='btn-group form-control ".$res[3]."'>".get_string('for_weeks', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array('href' => $urlannual));
echo "<button class='btn-group form-control ".$res[4]."'>".get_string('for_months', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a', array('href' => $urldetailmonth));
echo "<button class='btn-group form-control ".$res[5]."'>".get_string('month_detail', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::end_tag('div')."<br><br>";
$filtro->display();
echo html_writer::end_tag('div');
echo block_control_sesion_table_data($results);
echo html_writer::start_tag('a', array('href' => $urldownload));
echo "<button class='btn btn-info'>".get_string('download')."</button>";
echo html_writer::end_tag('a');
