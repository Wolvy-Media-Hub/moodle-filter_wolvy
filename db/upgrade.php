<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.


/**
 * Upgrade steps for the Wolvy video filter.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the filter.
 *
 * @param int $oldversion The version being upgraded from.
 * @return bool
 */
function xmldb_filter_wolvy_upgrade($oldversion) {
    if ($oldversion < 2026112800) {
        // 1.2.0: tags need a key made on the course page, signed with a site tag key.
        \filter_wolvy\tag::site_key();
        upgrade_plugin_savepoint(true, 2026112800, 'filter', 'wolvy');
    }

    return true;
}
