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
 * The viewer id sent to Wolvy for a Moodle user.
 *
 * It is what the watermark shows and what Wolvy records against each
 * playback session, so it is built from a template the site administrator
 * chooses: `{id}`, `{username}`, `{email}` and `{idnumber}` are replaced with
 * the user's values.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewer {
    /** The template used when none is set. */
    const DEFAULT_TEMPLATE = 'moodle-{id}';

    /**
     * Viewer id for a user.
     *
     * @param \stdClass $user A user record with id, username, email and idnumber.
     * @param string $template Template; '' uses DEFAULT_TEMPLATE.
     * @return string At most 64 bytes, never cut inside a character. '' for no user.
     */
    public static function id_for(\stdClass $user, string $template): string {
        if (empty($user->id)) {
            return '';
        }

        $template = trim($template) === '' ? self::DEFAULT_TEMPLATE : $template;

        $id = strtr($template, [
            '{id}' => (string) $user->id,
            '{username}' => (string) ($user->username ?? ''),
            '{email}' => (string) ($user->email ?? ''),
            '{idnumber}' => (string) ($user->idnumber ?? ''),
        ]);

        $id = self::clean($id);

        // A template whose fields are empty for this user (an empty ID number,
        // say) must not leave everyone with the same blank or shared id.
        $literal = trim(strtr($template, ['{id}' => '', '{username}' => '', '{email}' => '', '{idnumber}' => '']));
        if ($id === '' || $id === $literal) {
            $id = self::clean(strtr(self::DEFAULT_TEMPLATE, ['{id}' => (string) $user->id]));
        }

        return $id;
    }

    /**
     * Remove control characters and cut to Wolvy's 64-byte limit.
     *
     * @param string $id Candidate.
     * @return string
     */
    public static function clean(string $id): string {
        $id = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $id));

        if (strlen($id) > token::MAX_VIEWER_ID_BYTES) {
            $id = \core_text::str_max_bytes($id, token::MAX_VIEWER_ID_BYTES);
        }

        return $id;
    }
}
