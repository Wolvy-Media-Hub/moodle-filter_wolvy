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
 * Site administration settings for the Wolvy video filter.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'filter_wolvy/status',
        get_string('status', 'filter_wolvy'),
        \filter_wolvy\admin_status::describe()
    ));

    $settings->add(new admin_setting_configtext(
        'filter_wolvy/accountid',
        get_string('accountid', 'filter_wolvy'),
        get_string('accountid_desc', 'filter_wolvy'),
        '',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'filter_wolvy/signingsecret',
        get_string('signingsecret', 'filter_wolvy'),
        get_string('signingsecret_desc', 'filter_wolvy'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'filter_wolvy/viewerid',
        get_string('viewerid', 'filter_wolvy'),
        get_string('viewerid_desc', 'filter_wolvy'),
        \filter_wolvy\viewer::DEFAULT_TEMPLATE,
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configselect(
        'filter_wolvy/tokenlifetime',
        get_string('tokenlifetime', 'filter_wolvy'),
        get_string('tokenlifetime_desc', 'filter_wolvy'),
        7200,
        [
            3600 => format_time(3600),
            7200 => format_time(7200),
            14400 => format_time(14400),
            21600 => format_time(21600),
            43200 => format_time(43200),
            86400 => format_time(86400),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'filter_wolvy/guests',
        get_string('guests', 'filter_wolvy'),
        get_string('guests_desc', 'filter_wolvy'),
        'message',
        [
            'message' => get_string('guests_message', 'filter_wolvy'),
            'unsigned' => get_string('guests_unsigned', 'filter_wolvy'),
            'hide' => get_string('guests_hide', 'filter_wolvy'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'filter_wolvy/aspect',
        get_string('aspect', 'filter_wolvy'),
        get_string('aspect_desc', 'filter_wolvy'),
        '16:9',
        [
            '16:9' => '16:9',
            '4:3' => '4:3',
            '1:1' => '1:1',
            '21:9' => '21:9',
            '9:16' => '9:16',
        ]
    ));
}
