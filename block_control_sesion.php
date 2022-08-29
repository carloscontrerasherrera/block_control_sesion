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

// This file is the code of how the block is displayed in the sidebar.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/control_sesion/lib.php');

class block_control_sesion extends block_base {
    public function init() {
        $this->title = get_string('control_sesion', 'block_control_sesion');
    }
    // The PHP tag and the curly bracket for the class definition.
    // will only be closed after there is another function added in the next section.
    public function get_content() {
        global $COURSE, $DB, $USER;
        $context = context_course::instance($COURSE->id);
        if ($this->content !== null) {
            return $this->content;
        }
        $i = $this->instance->id;
        $this->content = new stdClass;
        $this->content->title = get_string('title_block', 'block_control_sesion');
        $this->content->text = '';
        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }
        // Check to see if we are in editing mode.
        // $canmanage = $this->PAGE->user_is_editing($this->instance->id); .
        // Check to see if we are in editing mode and that we can manage pages.
        $showsessions = has_capability('block/control_sesion:allsessions', $context);
        $mysession = has_capability('block/control_sesion:mysession', $context);
        $id = 0;
        if (!$showsessions) {
            $id = $USER->id;
        }
        $this->content->text .= block_control_sesion_table_data(block_control_sesion_users_list($i, $USER->id, false, 1,
         (new DateTime("now"))->format("Y-m-d"), 1, 1, 2020, 2020, 0, (new DateTime("now"))->format("Y-m-d"), false));
        if (empty($this->config->visibleus)) {
            // Block not configured yet.
            if ($showsessions) {
                $this->content->footer = get_string('no_config', 'block_control_sesion');
            }
        } else {
            if ($this->config->visibleus || $showsessions) {
                $g = 0;
                if (!empty($this->config->grupo)) {
                    $g = $this->config->grupo;
                }
                $url = new moodle_url('/blocks/control_sesion/view.php', array( 'c' => $COURSE->id,
                'id' => $i, 'u' => $id, "g" => $g));
                $this->content->footer = html_writer::link($url, get_string('showdetail', 'block_control_sesion'));
            }
        }
        return $this->content;
    }
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = '<b>'.get_string('defaulttitle', 'block_control_sesion').'</b>';
            } else {
                $this->title = '<b>'.$this->config->title.'</b>';
            }
        }
    }
}
