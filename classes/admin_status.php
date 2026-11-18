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

namespace filter_wolvy;

/**
 * The status lines at the top of the settings page.
 *
 * Everything here is checked locally; no request is made to Wolvy.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_status {
    /**
     * HTML list of the checks and their results.
     *
     * @return string
     */
    public static function describe(): string {
        global $CFG;

        $items = [];

        $items[] = token::self_test()
            ? get_string('status_signer_ok', 'filter_wolvy')
            : get_string('status_signer_fail', 'filter_wolvy');

        $items[] = (int) get_config('filter_wolvy', 'accountid') > 0
            ? get_string('status_account_ok', 'filter_wolvy')
            : get_string('status_account_missing', 'filter_wolvy');

        $secret = trim((string) get_config('filter_wolvy', 'signingsecret'));
        if ($secret === '') {
            $items[] = get_string('status_secret_missing', 'filter_wolvy');
        } else if (!token::secret_looks_valid($secret)) {
            $items[] = get_string('status_secret_odd', 'filter_wolvy');
        } else {
            $items[] = get_string('status_secret_ok', 'filter_wolvy');
        }

        $host = parse_url($CFG->wwwroot, PHP_URL_HOST) ?: $CFG->wwwroot;
        $items[] = get_string('status_domain', 'filter_wolvy', $host);

        return \html_writer::alist(array_map('s', $items));
    }
}
