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

namespace filter_wolvy\admin;

/**
 * The account ID setting: a number, or an embed address to take it from.
 *
 * The Wolvy dashboard shows the account ID inside every video's embed
 * address (https://embed.wolvy.stream/{video}/{base64 account ID}), so an
 * administrator can paste that address, or the whole embed code, and the
 * number is kept.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_accountid extends \admin_setting_configtext {

    /**
     * Store the number, whatever form it was given in.
     *
     * @param string $data Submitted value.
     * @return string '' on success, or an error message.
     */
    public function write_setting($data) {
        return parent::write_setting(self::normalise((string) $data));
    }

    /**
     * A number, or the number inside an embed address; anything else unchanged.
     *
     * @param string $value Submitted value.
     * @return string
     */
    public static function normalise(string $value): string {
        $value = trim($value);

        if ($value === '' || preg_match('/^\d+$/', $value)) {
            return $value;
        }

        $value = rawurldecode(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (preg_match('~embed\.wolvy\.stream/[A-Za-z0-9._-]+/([A-Za-z0-9+/_=-]+)~', $value, $m)) {
            $b64 = strtr(rtrim($m[1], '='), '-_', '+/');
            $decoded = base64_decode($b64 . str_repeat('=', (4 - strlen($b64) % 4) % 4), true);

            if ($decoded !== false && preg_match('/^\d+$/', $decoded)) {
                return $decoded;
            }
        }

        return $value;
    }
}
