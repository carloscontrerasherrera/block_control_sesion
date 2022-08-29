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
// Code to edit the block configuration.
class block_control_sesion_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $COURSE, $DB, $CFG;
        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));
        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('title', 'block_control_sesion'));
        $mform->setDefault('config_title', '');
        $mform->setType('config_title', PARAM_TEXT);
        $mform->addElement('text', 'config_text', get_string('message', 'block_control_sesion'));
        $mform->setDefault('config_text', '');
        $mform->setType('config_text', PARAM_RAW);
        for ($i = 0; $i <= 23; $i++) {
            $h[$i] = $i;
        }
        $mform->addElement('select', 'config_ini', get_string('ini_hour', 'block_control_sesion'), $h);
        $mform->setDefault('config_ini', '7');
        $mform->setType('config_ini', PARAM_RAW);
        $meses = [];
        for ($y = 1; $y <= 12; $y++) {
            array_push($meses, block_control_sesion_monthname($y));
        }
        $options = array_combine(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12), $meses);
        $groupele[] = $mform->addElement('select', 'config_month_ini', get_string('month_ini', 'block_control_sesion'), $options);
        $mform->setDefault('config_month_ini', '9');
        $mform->setType('config_month_ini', PARAM_INT);
        $mform->addElement('text', 'config_interval', get_string('interval', 'block_control_sesion'));
        $mform->setDefault('config_interval', '20');
        $mform->setType('config_interval', PARAM_INT);
        $mform->addElement('selectyesno', 'config_visibleus', get_string('visible_us', 'block_control_sesion'));
        $mform->setDefault('config_visibleus', 1);
        $mform->setType('config_visibleus', PARAM_INT);
        $groups = array(get_string('all'));
        $ids = array(0);
        if ($res = $DB->get_records_sql('SELECT id, name from {groups} g WHERE courseid='.$COURSE->id.' ORDER BY id DESC')) {
            foreach ($res as $g) {
                array_push($groups, $g->name);
                array_push($ids, $g->id);
            }
        }
        $options = array_combine($ids, $groups);
        $select = $mform->addElement('select', 'config_group', get_string('defaultgroup', 'block_control_sesion'), $options);
        $mform->setType('config_group', PARAM_INT);
        $mform->addElement('selectyesno', 'config_showcol', get_string('showcol', 'block_control_sesion'));
        $mform->setDefault('config_showcol', 1);
        $mform->setType('config_showcol', PARAM_INT);
        $mform->addElement('text', 'config_red', '<div style="padding:10px;background-color:red;">'
            .get_string('interac_day', 'block_control_sesion').' &lt;</div>');
        $mform->setDefault('config_red', '3');
        $mform->setType('config_red', PARAM_INT);
        $mform->addElement('text', 'config_orange', '<div style="padding:10px;background-color:orange;">'
            .get_string('interac_day', 'block_control_sesion').' &lt;</div>');
        $mform->setDefault('config_orange', '10');
        $mform->setType('config_orange', PARAM_INT);
        $mform->addElement('text', 'config_yellow', '<div style="padding:10px;background-color:yellow;">'
            .get_string('interac_day', 'block_control_sesion').' &lt;</div>');
        $mform->setDefault('config_yellow', '20');
        $mform->setType('config_yellow', PARAM_INT);
        $mform->hideIf('config_red', 'config_showcol', 'eq', 0);
        $mform->hideIf('config_orange', 'config_showcol', 'eq', 0);
        $mform->hideIf('config_yellow', 'config_showcol', 'eq', 0);
    }
}
