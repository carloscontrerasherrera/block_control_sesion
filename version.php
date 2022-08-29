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
defined('MOODLE_INTERNAL') || die();
$plugin->component = 'block_control_sesion';
$plugin->version = 2022082901;  // Versión del bloque por fecha/hora -> YYYYMMDDHH (year, month, day, 24-hr time).
$plugin->requires = 2010112400;  // YYYYMMDDHH (This is the release version for Moodle 2.0).
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.0-r4';
