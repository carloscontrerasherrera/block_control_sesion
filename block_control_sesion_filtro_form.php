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
 // Form for header filters.
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/formslib.php");
require_login();

class block_control_sesion_filtro_form extends moodleform {
    public function definition() {
        // Se añade un campo, se captura desde una variable en view,se pasa en las urls,
        // se añade a to_form y se hace un set_data y se obtiene desde from_form.
        global $tipofiltro, $groupfilter, $DB, $COURSE;
        $mform = $this->_form;
        // Comprueba si hay nuevo group seleccionado en el filtro_form
        // si no, coge el pasado por parámetro que es la última selección.
        $group = optional_param('group', -1, PARAM_INT);
        $groupfilter = $group;
        if ($group == -1) {
            $group = optional_param('g', -1, PARAM_INT);
            $groupfilter = $group;
        }
        $groups = array(get_string('all'));
        $ids = array(0);
        if ($res = $DB->get_records_sql('SELECT id, name from {groups} g WHERE courseid='.$COURSE->id.' ORDER BY id DESC')) {
            foreach ($res as $g) {
                array_push($groups, $g->name);
                array_push($ids, $g->id);
            }
        }
        $context = context_course::instance($COURSE->id);
        $showsessions = has_capability('block/control_sesion:allsessions', $context);
        if ($showsessions) {
            $options = array_combine($ids, $groups);
            $select = $mform->addElement('select', 'group', get_string('group'), $options);
            $mform->setType('group', PARAM_INT);
        } else {
            $mform->addElement('hidden', 'group');
            $mform->setType('group', PARAM_INT);
        }
        $groupele = array();
        if ($showsessions && $tipofiltro != 0 && $tipofiltro != 3 && $tipofiltro != 4 ) {
            $names = array(get_string('all'));
            $users = array(0);
            if ($groupfilter == 0) {
                $sqlus = 'SELECT * FROM {user} WHERE id in (SELECT r.userid FROM {role_assignments} r, '
                        .'{context} o where o.id=r.contextid and o.instanceid='.$COURSE->id.')';
            } else {
                $sqlus = 'SELECT * FROM {user} WHERE id in (SELECT r.userid FROM {groups_members} r WHERE r.groupid='
                        .$groupfilter.')';
            }
            if ($res = $DB->get_records_sql($sqlus)) {
                foreach ($res as $u) {
                    array_push($names, $u->firstname.' '.$u->lastname);
                    array_push($users, $u->id);
                }
            }
            $options = array_combine($users, $names);
            $mform->addElement('select', 'user', get_string('user'), $options);
        } else {
            $mform->addElement('hidden', 'user');
            $mform->setType('user', PARAM_INT);
        }
        if ($tipofiltro == 3 || $tipofiltro == 5) {
            $months = [];
            for ($y = 1; $y <= 12; $y++) {
                array_push($months, block_control_sesion_monthname($y));
            }
            $groupele[] = $mform->createElement('static', 'description', '', get_string('month').':&nbsp;&nbsp;');
            $options = array_combine(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12), $months);
            $groupele[] = $mform->createElement('select', 'month', get_string('month'), $options);
        } else {
            $mform->addElement('hidden', 'month');
            $mform->setType('month', PARAM_INT);
        }
        $hoy = new DateTime("now");
        $mform->setDefault('month', $hoy->format('m'));
        if ($tipofiltro >= 3) {
            $mform->addElement('hidden', 'date');
            $mform->setType('date', PARAM_RAW);
            if ($tipofiltro == 4) {
                // Por course completo.
                $groupele[] = $mform->createElement('static', 'description', '', get_string('course')
                            .':&nbsp;&nbsp;');
                $names = array();
                $valores = array();
                for ($x = $hoy->format('Y') - 10; $x <= block_control_sesion_fix_courseyear($hoy); $x++) {
                    array_push($names, $x.'-'.($x + 1));
                    array_push($valores, $x);
                }
                $options = array_combine($valores, $names);
                $groupele[] = $mform->createElement('select', 'course', get_string('course'), $options);
                $mform->setType('course', PARAM_INT);
                $mform->addElement('hidden', 'year');
                $mform->setType('year', PARAM_INT);
            } else {
                // Por semanas o mes detallado.
                $groupele[] = $mform->createElement('static', 'description', '', ucwords(get_string('year'))
                            .':&nbsp;&nbsp;');
                $options = array_combine(range($hoy->format('Y') - 10, $hoy->format('Y')), range($hoy->format('Y') - 10,
                            $hoy->format('Y')));
                $groupele[] = $mform->createElement('select', 'year', get_string('year'), $options);
                $mform->setType('year', PARAM_INT);
                $mform->addElement('hidden', 'course');
                $mform->setType('course', PARAM_INT);
            }
            $mform->addElement('hidden', 'date_fin');
            $mform->setType('date_fin', PARAM_RAW);
        } else {
            $groupele[] = $mform->createElement('date_selector', 'date', '');
            $mform->setType('date', PARAM_RAW);
            $groupele[] = $mform->createElement('date_selector', 'date_fin', get_string('until', 'block_control_sesion'));
            $mform->setType('date_fin', PARAM_RAW);
            $mform->addElement('hidden', 'year');
            $mform->setType('year', PARAM_INT);
            $mform->addElement('hidden', 'course');
            $mform->setType('course', PARAM_INT);
        }
        $mform->setDefault('year', (new DateTime("now"))->format('Y'));
        $mform->setDefault('course', block_control_sesion_fix_courseyear($hoy));
        $groupele[] = $mform->createElement('submit', 'submitbutton', get_string('filter', 'block_control_sesion'));
        $mform->addGroup($groupele, 'group_ele_filtro', get_string('session_date', 'block_control_sesion'), ' ', false);
        $mform->setAdvanced('optional');
        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid', PARAM_RAW);
        $mform->addElement('hidden', 'c');
        $mform->setType('c', PARAM_RAW);
        $mform->addElement('hidden', 'id', '0');
        $mform->setType('id', PARAM_RAW);
        $mform->addElement('hidden', 't', 0);
        $mform->setType('t', PARAM_RAW);
        $mform->addElement('hidden', 'a', false);
        $mform->setType('a', PARAM_RAW);
    }
}
