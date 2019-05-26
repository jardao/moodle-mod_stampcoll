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
 * Keeps track of upgrades to the Stamp collection module
 *
 * @package    mod_stampcoll
 * @copyright  2007 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Checks if some upgrade steps are needed and performs them eventually
 *
 * @param int $oldversion the current version we are upgrading from
 * @return true
 */
function xmldb_stampcoll_upgrade($oldversion = 0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // The module must have version 2011120716 (release v2.0.0) at this point.

        // @mfernandriu modifications
    if ($oldversion < 2019052301) {

        // Define field grademaxgrade to be added to stampcoll.
        $table = new xmldb_table('stampcoll');
        $field = new xmldb_field('grademaxgrade', XMLDB_TYPE_FLOAT, '10', null, null, null, null, 'displayzero');

        // Conditionally launch add field grademaxgrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pointsperstamp', XMLDB_TYPE_FLOAT, '10', null, null, null, null, 'grademaxgrade');

        // Conditionally launch add field pointsperstamp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('gradecat', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'pointsperstamp');

        // Conditionally launch add field gradecat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('completionstamps', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'gradecat');

        // Conditionally launch add field completionstamps.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

         // Define table stampcoll_grades to be created.
        $table = new xmldb_table('stampcoll_grades');

        // Adding fields to table stampcoll_grades.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('stampcollid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('grade', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table stampcoll_grades.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for stampcoll_grades.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Stampcoll savepoint reached.
        upgrade_mod_savepoint(true, 2019052301, 'stampcoll');
    }


    return true;
}
