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
 * Callbacks of the Wolvy video filter.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add "Wolvy video tags" to the course menu (More) for people who can make tags.
 *
 * @param navigation_node $navigation The course node.
 * @param stdClass $course The course.
 * @param context $context The course context.
 */
function filter_wolvy_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (!has_capability('filter/wolvy:embed', $context)) {
        return;
    }
    $navigation->add(
        get_string('tagpage', 'filter_wolvy'),
        new moodle_url('/filter/wolvy/tag.php', ['courseid' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'filter_wolvy_tags'
    );
}
