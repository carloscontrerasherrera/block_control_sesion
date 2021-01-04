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
function block_control_sesion_users_list($instance, $user, $detailed = false, $reporttype = 0, $date = "now", $totaldays = 1,
                        $month = 1, $year = 1, $course = 1, $group = 0, $datefin = "now") {
    global $COURSE, $DB, $PAGE, $USER, $config;
    $dateabrev = get_string('date_abrev', 'block_control_sesion');
    $userfilter = "";
    $rows = array();
    $valuedays = 0;
    $config = block_control_sesion_get_config($instance);
    $ini = 7;
    $interval = 30;
    if (isset($config->ini)) {
        $ini = $config->ini;
    }
    if (isset($config->interval)) {
        $interval = $config->interval;
    }
    if ($user != 0) {
        $userfilter = " AND r.userid=".$user;
    }
    if ($reporttype == 0) {
        if ($group == 0) {
            $sqlus = 'SELECT u.id, u.firstname, u.lastname,MAX(m.timecreated)last FROM {user} u '
            .' LEFT JOIN {logstore_standard_log} m ON m.userid=u.id WHERE u.id in (SELECT r.userid '
            .' FROM {role_assignments} r, {context} o where o.id=r.contextid and o.instanceid='.$COURSE->id.$userfilter
            .') GROUP BY u.id';
        } else {
            $sqlus = 'SELECT u.id, u.firstname, u.lastname,MAX(m.timecreated)last FROM {user} u '
            .' LEFT JOIN {logstore_standard_log} m ON m.userid=u.id WHERE u.id in (SELECT r.userid '
            .' FROM {groups_members} r WHERE r.groupid='.$group.') GROUP BY u.id';
        }
    } else {
        if ($group == 0) {
            $sqlus = 'SELECT id, firstname, lastname FROM {user} WHERE id in (SELECT r.userid '
            .' FROM {role_assignments} r, {context} o where o.id=r.contextid and o.instanceid='.$COURSE->id.$userfilter.
            ')';
        } else {
            $sqlus = 'SELECT id, firstname, lastname FROM {user} WHERE id in (SELECT r.userid FROM {groups_members} r '
            .' WHERE r.groupid='.$group.')';
        }
    }
    if ($users = $DB->get_records_sql($sqlus)) {
        $now = block_control_sesion_get_start($date, $ini);
        $tomorrow = block_control_sesion_get_end($now, $totaldays);
        if (($datefin != "now" || $datefin == '') && $totaldays == 1) {
            $tomorrow = block_control_sesion_get_end(block_control_sesion_get_start($datefin, $ini), 1);
        }
        // Cabeceras de cada type de tabla.
        if ($reporttype <= 1) {
            $valuedays = date_diff($tomorrow, $now )->days;
            $data = array();
            array_push($data, get_string('records_between', 'block_control_sesion').'<br>'.$now->format($dateabrev.' H:i')
                .'&nbsp;&nbsp;'.get_string('and', 'block_control_sesion').'&nbsp;&nbsp;'.$tomorrow->format($dateabrev.' H:i')
                ."<br>".$valuedays." ".get_string('days')."<br>");
            array_push($rows, $data);
        }
        $data = array();
        if ($reporttype == 0) {
            array_push($data, "<th>".get_string('student', 'block_control_sesion'));
            array_push($data, get_string('last_login', 'block_control_sesion'));
            array_push($data, get_string('start', 'block_control_sesion'));
            array_push($data, get_string('final', 'block_control_sesion'));
            array_push($data, get_string('interacts', 'block_control_sesion'));
            array_push($data, get_string('estimated_time', 'block_control_sesion'));
            array_push($data, "");
        }
        // No se pone cabecera para cada usuario type 1.
        if ($reporttype == 2) {
            $final = new DateTime($datefin);
            $h = new DateTime($date);
            $data = array();
            array_push($data, get_string('records_between', 'block_control_sesion').'<br>'.$now->format($dateabrev.' H:i')
                    .'&nbsp;&nbsp;'.get_string('and', 'block_control_sesion').'&nbsp;&nbsp;'.$tomorrow->format($dateabrev.' H:i')
                    ."<br><br>");
            array_push($rows, $data);
            $data = array();
            array_push($data, "<th>".get_string('student', 'block_control_sesion'));
            while ($h <= $final) {
                array_push($data, block_control_sesion_dayname($h->format('w'), true)."<br>".$h->format('d-m'));
                $h = (new DateTime($h->format('Y-m-d').' + 1 days'));
            }
            array_push($data, ucwords(get_string('total')));
        }
        if ($reporttype == 3) {
            array_push($data, block_control_sesion_monthname($month).' - '.$year);
            array_push($rows, $data);
            $data = array();
            array_push($data, "<th>".get_string('student', 'block_control_sesion'));
            $h = new DateTime($year.'-'.$month.'-01');
            $m = $h->format("m");
            for ($s = 1; $s <= 5; $s++) {
                $final = new DateTime($h->format('Y-m-d H:i:s')."+ 6 days");
                if ($m != $final->format("m")) {
                    $final = new DateTime($h->format("Y-m-").$h->format("t").$h->format(" H:i:s"));
                }
                array_push($data, $h->format('d')." → ".$final->format('d'));
                $h = new DateTime($h->format('Y-m-d H:i:s')."+ 7 days");
            }
            array_push($data, get_string('month_total', 'block_control_sesion'));
            array_push($data, "");
        }
        if ($reporttype == 4) {
            array_push($data, get_string('course').' '.$course.'-'.($course + 1));
            array_push($rows, $data);
            $data = array();
            array_push($data, "<th>".get_string('student', 'block_control_sesion'));
            for ($x = 1; $x <= 12; $x++) {
                // Sustituir year por curso.
                $res = block_control_sesion_fix_coursemonth($x, $course);
                $urldetailmonth = new moodle_url('/blocks/control_sesion/view.php' ,
                    array('id' => $instance, 'c' => $COURSE->id, 't' => 2, 'u' => 0, "f" => $res["year"].'-'.$res["month"]
                        .'-01', "ff" => $res["year"].'-'.$res["month"].'-'.block_control_sesion_monthdays($res["month"],
                        $res["year"]), "g" => $group));
                $text = '<a href="'.$urldetailmonth.'">'.substr(block_control_sesion_monthname($res["month"]), 0, 3).'</a>';
                array_push($data, $text);
            }
            array_push($data, "TOT");
        }
        if ($reporttype == 5) {
            array_push($data, block_control_sesion_monthname($month).' - '.$year);
            array_push($rows, $data);
            $data = array();
            array_push($data, "<th>");
            for ($x = 1; $x <= 7; $x++) {
                array_push($data, block_control_sesion_dayname($x));
            }
        }
        array_push($rows, $data);
        foreach ($users as $u) {
            // Crea un elemento de la lista para cada página.
            if ($user != 0 && $user != $u->id) {
                continue;
            }
            $data = array();
            $sql = 'FROM {logstore_standard_log} m where userid='.$u->id.' AND courseid='.$COURSE->id
                .' AND (m.timecreated)>="'.block_control_sesion_serverdate($now->format('Y-m-d H:i:s')).'" AND (m.timecreated)<="'
                .block_control_sesion_serverdate($tomorrow->format('Y-m-d H:i:s')).'"';
            $horaini = '';
            $interval = '';
            if ($hini = $DB->get_record_sql('SELECT MIN(timecreated) timecreated '.$sql)) {
                if (!empty($hini->timecreated)) {
                    $horaini = date("H:i:s", $hini->timecreated);
                    if ($hfin = $DB->get_record_sql('SELECT MAX(timecreated) timecreated '.$sql)) {
                        $horafin = date("H:i:s", $hfin->timecreated);
                        $interval = ' ['.$horaini." → ".$horafin.']'."<br>";
                    }
                }
            }
            // Datos de cada usuario. Contenido de las tablas.
            // Color de la celda resaltada según límites.
            if ($reporttype == 0) {
                $res = block_control_sesion_dayusertime($u->id, $now->format('Y-m-d'), $ini, $interval, 1,
                                                        $tomorrow->format('Y-m-d'));
                $time = "&nbsp;";
                if ($res["total"] != 0) {
                    $time = block_control_sesion_time_sec($res["total"]);
                }
                $urldetail = new moodle_url('/blocks/control_sesion/view.php' , array('c' => $COURSE->id, 'id' => $instance,
                        't' => 1, 'a' => true, "f" => $date, "ff" => $date, "u" => $u->id, "g" => $group));
                array_push($data, $u->firstname." ".$u->lastname);
                array_push($data, date(get_string('date_abrev', 'block_control_sesion')." H:i:s", $u->last));
                array_push($data, $res["start"]);
                array_push($data, $res["final"]);
                $color = block_control_sesion_get_color($res["interacts"], $valuedays);
                array_push($data, $color.intval($res["interacts"])." ".get_string('abrev_int', 'block_control_sesion'));
                if ($res["total"] != 0 ) {
                    array_push($data, $time);
                    array_push($data, '<a href="'.$urldetail.'">'.get_string('details', 'block_control_sesion').'</a>');
                } else {
                    array_push($data, get_string('no_time_day', 'block_control_sesion'));
                }
            }
            if ($reporttype == 1) {
                $res = block_control_sesion_dayusertime($u->id, $now->format('Y-m-d'), $ini, $interval, 1,
                            $tomorrow->format('Y-m-d'));
                $time = "&nbsp;";
                if ($res["total"] != 0) {
                    $time = block_control_sesion_time_sec($res["total"]);
                }
                if ($res["total"] != 0) {
                    $t = '<font color="red">'.$interval.$time.' '.get_string('estimated', 'block_control_sesion').'</font><br>';
                } else {
                    $t = '<font color="red">'.get_string('no_time_day', 'block_control_sesion').'</font>  <br>';
                }
                if ($detailed) {
                    $data = array("<blue>".html_writer::tag('div', '<b>'.$u->firstname." ".$u->lastname),
                                intval($res["interacts"])." ".get_string('interacts', 'block_control_sesion'), $t);
                    array_push($rows, $data);
                    $data = array();
                    array_push($data, "<th>".get_string('start', 'block_control_sesion'));
                    array_push($data, get_string('final', 'block_control_sesion'));
                    array_push($data, get_string('estimated_time', 'block_control_sesion'));
                    array_push($rows, $data);
                    foreach ($res["details"] as $d) {
                        array_push($rows, array($d[0], $d[1], $d[2]));
                    }
                    $data = array();
                } else {
                    $info = html_writer::tag('div', '<b>'.$u->firstname." ".$u->lastname.'</b>');
                    $info .= intval($res["interacts"])." ".get_string('user_interacts', 'block_control_sesion').'<br>';
                    $info .= $t;
                    array_push($data, $info);
                }
            }
            if ($reporttype == 2) {
                $t = 0;
                $final = new DateTime($datefin);
                $h = new DateTime($date);
                if (date_diff($final, $h)->days > 10) {
                    array_push($data, '<b>'.$u->firstname." ".$u->lastname[0].'.</b>');
                } else {
                    array_push($data, '<b>'.$u->firstname." ".$u->lastname.'.</b>');
                }
                while ($h <= $final) {
                    $res = block_control_sesion_dayusertime($u->id, $h->format('Y-m-d'), $ini, $interval, 1, '');
                    $t = $t + $res["total"];
                    $time = "&nbsp;";
                    if ($res["total"] != 0) {
                        $time = intval($res['interacts']).' '.get_string('abrev_int', 'block_control_sesion').'<br>'
                                .block_control_sesion_time_sec($res["total"]);
                        $urldetail = new moodle_url('/blocks/control_sesion/view.php' , array('id' => $instance,
                            'c' => $COURSE->id , 't' => 1, 'a' => true, "f" => $h->format('Y-m-d'), "ff" => $h->format('Y-m-d'),
                            "u" => $u->id, "g" => $group));
                        $time = '<a href="'.$urldetail.'">'.$time.'</a>';
                    }
                    $color = block_control_sesion_get_color($res["interacts"], 1);
                    array_push($data, $color.$time);
                    $h = (new DateTime($h->format('Y-m-d').' + 1 days'));
                }
                array_push($data, '<b>'.block_control_sesion_time_sec($t).'</b>');
            }
            if ($reporttype == 3) {
                $t = 0;
                array_push($data, '<b>'.$u->firstname." ".$u->lastname.'.</b>');
                $urlmonthdetail = new moodle_url('/blocks/control_sesion/view.php' , array('id' => $instance, 'c' => $COURSE->id,
                    't' => 5, 'a' => true, "m" => $month, "y" => $year, "u" => $u->id, "g" => $group));
                $now = new DateTime($year.'-'.$month.'-01');
                for ($s = 1; $s <= 5; $s++) {
                    $finalday = date("t", strtotime($now->format('Y-m-d')));
                    $days = 7;
                    $final = new DateTime($now->format('Y-m-d H:i:s')."+ 6 days");
                    if ($now->format("m") != $final->format("m")) {
                        $days = $finalday - $now->format("d") + 1;
                    }
                    $res = block_control_sesion_dayusertime($u->id, $now->format('Y-m-d'), $ini, $interval, $days, '');
                    $t = $t + $res["total"];
                    $time = "&nbsp;";
                    if ($res["total"] != 0) {
                        $time = intval($res['interacts']).' int.<br>'.block_control_sesion_time_sec($res["total"]);
                    }
                    $color = block_control_sesion_get_color($res["interacts"], 7);
                    array_push($data, $color.$time);
                    $now = new DateTime($now->format('Y-m-d H:i:s')."+ 7 days");
                }
                array_push($data, '<nocolor><b>'.block_control_sesion_time_sec($t).'</b>');
                array_push($data, '<nocolor><a href="'.$urlmonthdetail.'">'.get_string('details', 'block_control_sesion')
                            .'</a>');
            }
            if ($reporttype == 4) {
                $t = 0;
                array_push($data, '<b>'.$u->firstname." ".$u->lastname[0].'.</b>');
                for ($a = 1; $a <= 12; $a++) {
                    $fix = block_control_sesion_fix_coursemonth($a, $course);
                    $now = new DateTime($fix["year"].'-'.$fix["month"].'-01');
                    $days = date ( "t", strtotime($now->format('Y-m-d')));
                    $res = block_control_sesion_dayusertime($u->id, $now->format('Y-m-d'), $ini, $interval, $days, '');
                    $t = $t + $res["total"];
                    $time = "&nbsp;";
                    if ($res["total"] != 0) {
                        $urlmonthdetail = new moodle_url('/blocks/control_sesion/view.php', array('id' => $instance,
                                'c' => $COURSE->id, 't' => 5, 'a' => true, 'u' => $u->id, "m" => $fix["month"],
                                "y" => $fix["year"] , "g" => $group));
                        $time = '<a href="'.$urlmonthdetail.'">'.intval($res['interacts']).' int.<br>'
                            .block_control_sesion_time_sec($res["total"]).'</a>';
                    }
                    $color = block_control_sesion_get_color($res["interacts"], $days);
                    array_push($data, $color.$time);
                }
                array_push($data, '<b>'.block_control_sesion_time_sec($t).'</b>');
            }
            if ($reporttype == 5) {
                $t = 0;
                $now = new DateTime($year.'-'.$month.'-01');
                $days = date ("t", strtotime($now->format('Y-m-d')));
                array_push($data, '<b>'.$u->firstname." ".$u->lastname.'.</b>');
                $now = new DateTime($now->format('Y-m').'-01');
                $ds = $now->format('w');
                if ($ds == 0) {
                    $ds = 7;
                }
                for ($d = 1; $d <= $ds - 1; $d++) {
                    array_push($data, "-");
                }
                for ($d = 1; $d <= $days; $d++) {
                    $f = new DateTime($now->format('Y-m-'.$d));
                    $res = block_control_sesion_dayusertime($u->id, $f->format('Y-m-d'), $ini, $interval, 1, '');
                    $t = $t + $res["total"];
                    $time = "<br>&nbsp;";
                    if ($res["total"] != 0) {
                        $time = block_control_sesion_time_sec($res["total"]);
                        $urldetail = new moodle_url('/blocks/control_sesion/view.php' , array('id' => $instance,
                                'c' => $COURSE->id, 't' => 1, 'a' => true, "f" => $f->format('Y-m-d'), "u" => $u->id,
                                "ff" => $f->format('Y-m-d'), "g" => $group));
                        $time = '<a href="'.$urldetail.'">'.intval($res['interacts']).' '
                                .get_string('abrev_int', 'block_control_sesion').'<br>'.$time.'</a>';
                    }
                    $color = block_control_sesion_get_color($res["interacts"], 1);
                    if ($color == "<nocolor>") {
                        if ($f->format('Y-m-d') == (new DateTime("now"))->format('Y-m-d')) {
                            $color = "<blue>";
                        }
                    }
                    if ($ds == 6 || $ds == 7) {
                        $color = "<gray>";
                    }
                    array_push($data, $color."<b>$d</b><br><center>".$time."</center>");
                    $ds++;
                    if ($ds == 8) {
                        array_push($rows, $data);
                        $data = array("");
                        $ds = 1;
                    }
                }
                array_push($rows, $data);
                $data = array(mb_strtoupper(get_string('total')));
                array_push($data, '<b>'.block_control_sesion_time_sec($t).'</b>');
            }
            array_push($rows, $data);
        }
        $res["values"] = $rows;
        $res["valuedays"] = $valuedays;
        return $res;
    }
}
function block_control_sesion_get_start($date, $ini) {
    if ($ini == "") {
        $ini = "7";
    }
    $actuallytime = (new DateTime("now"))->format("H:i:s");
    if ($date == "now") {
        $actuallytime = "12:00:00";
    }
    $day = new DateTime($date." ".$actuallytime);
    $limit = new DateTime($day->format('Y-m-d').' '.$ini.':00:00');
    $now = $limit;
    $tomorrow = new DateTime($limit->format('Y-m-d H:i:s')."+ 1 days");
    if ($day < $limit && $day == $now) {
        $now = new DateTime($now->format('Y-m-d H:i:s')."- 1 days");
        $tomorrow = new DateTime($tomorrow->format('Y-m-d H:i:s')."- 1 days");
    }
    return $now;
}
function block_control_sesion_get_end($dateini, $totaldays) {
    $end = new DateTime($dateini->format('Y-m-d H:i:s')."+ ".$totaldays." days");
    return $end;
}
function block_control_sesion_time_sec($seconds) {
    $minutes = round($seconds / 60);
    $hours = intdiv($minutes, 60);
    $minutes = $minutes % 60;
    return $hours." h. ".$minutes." min.";
}
function block_control_sesion_last_login($user) {
    global $COURSE, $DB;
    $dateabrev = get_string('date_abrev', 'block_control_sesion');
    $sql = 'SELECT MAX(timecreated) last FROM {logstore_standard_log} m where userid='.$user.' AND courseid='.$COURSE->id;
    if ($evento = $DB->get_record_sql($sql)) {
        $last = new DateTime();
        $last->setTimeStamp($evento->last);
        return $last->format($dateabrev.' H:i:s');
    }
    return "";
}
function block_control_sesion_dayusertime($user, $date, $ini, $interval, $totaldays, $datefin = '') {
    global $COURSE, $DB;
    $dateabrev = get_string('date_abrev', 'block_control_sesion');
    if ($ini == "") {
        $ini = "7";
    }
    if (strlen($ini) == 1) {
        $ini = '0'.$ini;
    }
    if ($interval == "") {
        $interval = "30";
    }
    $interacts = 0;
    $now = new DateTime($date.' '.$ini.':00:00', core_date::get_user_timezone_object());
    if ($datefin == '') {
        $tomorrow = block_control_sesion_get_end($now, $totaldays);
    } else {
        $tomorrow = new DateTime($datefin.' '.$ini.':00:00', core_date::get_user_timezone_object());
    }
    $sql = 'SELECT id,timecreated FROM {logstore_standard_log} m where courseid='.$COURSE->id.' AND userid='
            .$user.' AND from_unixtime(m.timecreated)>="'.block_control_sesion_serverdate($now->format('Y-m-d H:i:s'))
            .'" AND from_unixtime(m.timecreated)<="'.block_control_sesion_serverdate($tomorrow->format('Y-m-d H:i:s'))
            .'" ORDER BY id';
    $total = 0;
    $step = "";
    $result["start"] = '';
    $result["final"] = '';
    $details = array();
    if ($events = $DB->get_records_sql($sql)) {
        $prev = new DateTime();
        $prev->setTimeStamp(0);
        $start = 0;
        $secondsprev = 0;
        $first = "";
        $interacts = count($events);
        $details = array();
        foreach ($events as $ev) {
            $actual = new DateTime();
            $actual->setTimeStamp($ev->timecreated);
            if ($start == 0) {
                $start = 1;
                $first = $actual->format($dateabrev.' H:i:s');
                $result["start"] = $first;
            } else {
                $diff = date_diff($actual, $prev);
                $seconds = ($diff->h * 3600 ) + ( $diff->i * 60 ) + $diff->s;
                if ($seconds <= ($interval * 60)) {
                    if ($first == "") {
                        $first = $prev->format($dateabrev.' H:i:s');
                    }
                    $secondsprev += $seconds;
                } else {
                    if ($secondsprev > 0) {
                        $step = $step.$first." → ".$prev->format($dateabrev.' H:i:s');
                        $total = $total + $secondsprev;
                        $step = $step." → ".round($secondsprev / 60)." ".get_string('minutes')."<br>";
                        $line = array($first, $prev->format($dateabrev.' H:i:s'), round($secondsprev / 60)." "
                                .get_string('minutes'));
                        array_push($details, $line);
                    }
                    if ($first == "") {
                        $step = $step.$prev->format($dateabrev.' H:i:s')."<br>";
                        $line = array($prev->format($dateabrev.' H:i:s'), '', '');
                        array_push($details, $line);
                    }
                    $first = "";
                    $secondsprev = 0;
                }
            }
            $last = $prev;
            $prev = $actual;
        }
        $result["final"] = $actual->format($dateabrev.' H:i:s');
        if ($secondsprev > 0) {
            if ($first != "") {
                $last = $prev;
            }
            $total = $total + $secondsprev;
            $step = $step.$first." → ".$last->format($dateabrev.' H:i:s');
            $step = $step." → ".round($secondsprev / 60)." ".get_string('minutes')."<br>";
            $line = array($first, $last->format($dateabrev.' H:i:s'), round($secondsprev / 60)." "
                    .get_string('minutes'));
            array_push($details, $line);
        } else {
            $step = $step.$actual->format($dateabrev.' H:i:s');
            $line = array($actual->format($dateabrev.' H:i:s'), '', '');
            array_push($details, $line);
        }
    }
    $result["details"] = $details;
    $result["interacts"] = $interacts;
    $result["total"] = $total;
    $result["steps"] = $step;
    return $result;
}
function block_control_sesion_table_data($data) {
    $values = $data["values"];
    $valuedays = $data["valuedays"];
    $result = "";
    $type = "th";
    $arg = array();
    $result .= html_writer::start_tag('table', array("class" => "table"));
    $num = 0;
    if (is_array($values)) {
        foreach ($values as $row) {
            $span = array();
            if (count($row) == 1) {
                $span = array('colspan' => 8, 'bgcolor' => '#eee');
            }
            $result .= html_writer::start_tag('tr');
            foreach ($row as $field) {
                if (substr($field, 0, 4) == '<th>') {
                    $field = substr($field, 4);
                    $type = 'th';
                    $span["bgcolor"] = '#bbb';
                }
                if (substr($field, 0, 6) == '<blue>') {
                    $field = substr($field, 6);
                    $type = 'th';
                    $span["bgcolor"] = '#6cc';
                }
                if (substr($field, 0, 6) == '<gray>') {
                    $field = substr($field, 6);
                    $type = 'th';
                    $span["bgcolor"] = '#eee';
                }
                if (substr($field, 0, 5) == '<red>') {
                    $field = substr($field, 5);
                    $type = 'td';
                    $span["bgcolor"] = '#FF7A7A';
                }
                if (substr($field, 0, 8) == '<orange>') {
                    $field = substr($field, 8);
                    $type = 'td';
                    $span["bgcolor"] = '#FFBD8C';
                }
                if (substr($field, 0, 8) == '<yellow>') {
                    $field = substr($field, 8);
                    $type = 'td';
                    $span["bgcolor"] = '#FCFF8C';
                }
                if (substr($field, 0, 10) == '<nocolor>') {
                    $field = substr($field, 10);
                    $type = 'td';
                    $span["bgcolor"] = '';
                }
                $result .= html_writer::start_tag($type, $span);
                $result .= $field;
                $result .= html_writer::end_tag($type);
            }
            if ($num > 0) {
                $type = 'td';
            }
            $result .= html_writer::end_tag('tr');
            $num++;
        }
    }
    $result .= html_writer::end_tag('table');
    return($result);
}
function block_control_sesion_download_data($data) {
    $values = $data["values"];
    $downloadfilename = clean_filename("sessions.xlsx");
    // Creating a workbook.
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers.
    $workbook->send($downloadfilename);
    // Adding the worksheet.
    $myxls = $workbook->add_worksheet();
    // Print cellls.
    $y = 0;
    foreach ($values as $row) {
        $x = 0;
        foreach ($row as $field) {
            $myxls->write_string($y, $x, str_replace('&nbsp;', ' ', strip_tags($field)));
            $x++;
        }
        $y++;
    }
    $workbook->close();
}
function block_control_sesion_monthname($month) {
    global $CFG;
    setlocale(LC_TIME, $CFG->locale);
    $dateobj = DateTime::createFromFormat('!m', $month);
    return ucwords(strftime('%B', $dateobj->getTimeStamp()));
}
function block_control_sesion_dayname($day, $abrev=false) {
    global $CFG;
    $dsh = $day - date("w");
    setlocale(LC_TIME, $CFG->locale);
    $ref = new DateTime('+'.$dsh.' DAYS');
    $name = ucwords(strftime('%A', $ref->getTimeStamp()));
    if ($abrev) {
        $name = mb_substr($name, 0, 3, "UTF-8");
    }
    return $name;
}
function block_control_sesion_monthdays($month, $year) {
    $f = new DateTime($year.'-'.$month.'-01');
    return $f->format('t');
}
function block_control_sesion_time_gap() {
    global $DB;
    $difmyutc = 0;
    $totalgap = 0;
    if ($res = $DB->get_record_sql('SELECT TIMESTAMPDIFF(HOUR,UTC_TIMESTAMP(),LOCALTIMESTAMP()) dif,UTC_TIMESTAMP() hutc')) {
        // Server gap.
        $difmyutc = $res->dif;
        // User Country gap.
        $now = new DateTime("now");
        $utc = new DateTime($res->hutc);
        $diff = date_diff($now, $utc);
        $totalgap = -($difmyutc - $diff->h);
    }
    return $totalgap;
}
function block_control_sesion_serverdate($dateus) {
    $fu = new DateTime($dateus);
    $fs = new DateTime($dateus.'-'.block_control_sesion_time_gap().'HOUR');
    return $fs->format('Y-m-d H:i:s');
}
function block_control_sesion_get_config($instance) {
    global $COURSE, $DB;
    $blockrecord = $DB->get_record('block_instances', array('blockname' => 'control_sesion', 'id' => $instance), '*', MUST_EXIST);
    $blockinstance = block_instance('control_sesion', $blockrecord);
    return $blockinstance->config;
}
function block_control_sesion_get_color($valor, $valuedays) {
    global $config;
    if (!$config->showcol) {
        return "<nocolor>";
    }
    if ($valuedays <= 0) {
        return "nocolor";
    }
    $v = intdiv($valor, $valuedays);
    if ($v <= $config->red) {
        return "<red>";
    } else {
        if ($v <= $config->orange) {
            return "<orange>";
        } else {
            if ($v <= $config->yellow) {
                return "<yellow>";
            } else {
                return "<nocolor>";
            }
        }
    }
}
function block_control_sesion_fix_courseyear($date) {
    global $config;
    $res = $date->format('Y');
    if ($date->format('m') < $config->month_ini) {
        $res--;
    }
    return $res;
}
function block_control_sesion_fix_coursemonth($n, $course) {
    global $config;
    $res["month"] = $config->month_ini + $n - 1;
    $res["year"] = $course;
    if ($res["month"] > 12) {
        $res["month"] = $res["month"] - 12;
        $res["year"]++;
    }
    return $res;
}