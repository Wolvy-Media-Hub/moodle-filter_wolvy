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
 * Tags that only teachers can make.
 *
 * Anyone who can post text (a forum post, a profile) can type [wolvy id="…"].
 * So a tag gets a viewer token only when it carries a key made on this site
 * for that video and that course:
 *
 *     key = base64url( first 16 bytes of HMAC-SHA256(site tag key, "wolvy-tag|1|<course id>|<video id>") )
 *
 * Keys are made on the course page "Wolvy video tags" (tag.php), which needs
 * the capability filter/wolvy:embed in the course. The site tag key is a random
 * value made on install; it never leaves this server and is not the Wolvy
 * signing secret, so rotating that secret leaves every tag working.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag {
    /** The capability that lets a user make tags in a course. */
    const CAPABILITY = 'filter/wolvy:embed';

    /** Length of a key in characters (16 bytes, base64url). */
    const KEY_LENGTH = 22;

    /**
     * The site tag key, made the first time it is needed.
     *
     * Install and upgrade make it already (db/install.php, db/upgrade.php), so
     * the fallback here only runs on a site where that step was skipped.
     *
     * @return string 64 hexadecimal characters.
     */
    public static function site_key(): string {
        $key = (string) get_config('filter_wolvy', 'tagkey');
        if (!preg_match('/^[0-9a-f]{64}$/', $key)) {
            $key = bin2hex(random_bytes(32));
            set_config('tagkey', $key, 'filter_wolvy');
        }
        return $key;
    }

    /**
     * The key for one video in one course.
     *
     * @param string $videoid Wolvy video id, already validated.
     * @param int $courseid Course id.
     * @return string
     */
    public static function key(string $videoid, int $courseid): string {
        $mac = hash_hmac('sha256', 'wolvy-tag|1|' . $courseid . '|' . $videoid, self::site_key(), true);
        return token::base64url(substr($mac, 0, 16));
    }

    /**
     * Does the key belong to this video in this course?
     *
     * @param string $videoid Wolvy video id.
     * @param int $courseid Course the text is shown in; 0 when it is in none.
     * @param string $key The tag's key attribute.
     * @return bool
     */
    public static function verify(string $videoid, int $courseid, string $key): bool {
        if ($courseid <= 0 || $videoid === '' || strlen($key) !== self::KEY_LENGTH) {
            return false;
        }
        return hash_equals(self::key($videoid, $courseid), $key);
    }

    /**
     * The course a context belongs to: the course itself, or the course of an
     * activity or block in it. 0 for text outside any course (a user profile,
     * a category description, the dashboard).
     *
     * @param \context $context The filter's context.
     * @return int
     */
    public static function course_of(\context $context): int {
        $coursecontext = $context->get_course_context(false);
        return $coursecontext ? (int) $coursecontext->instanceid : 0;
    }

    /**
     * The whole tag, ready to paste into text in the course.
     *
     * @param string $videoid Wolvy video id, already validated.
     * @param int $courseid Course id.
     * @return string
     */
    public static function make(string $videoid, int $courseid): string {
        return '[wolvy id="' . $videoid . '" key="' . self::key($videoid, $courseid) . '"]';
    }

    /**
     * A video id from what a teacher pasted: the id itself, or an embed
     * address (https://embed.wolvy.stream/<id>/…) or embed code containing one.
     *
     * @param string $input What was pasted.
     * @return string The id, or '' when there is none.
     */
    public static function video_from_input(string $input): string {
        $input = trim($input);
        if (preg_match('~embed\.wolvy\.stream/([A-Za-z0-9._-]{4,64})(?:[/?"\'\s]|$)~', $input, $m)) {
            return text_filter::clean_video_id($m[1]);
        }
        return text_filter::clean_video_id($input);
    }
}
