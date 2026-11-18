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
 * The player markup.
 *
 * One iframe at embed.wolvy.stream, sized by a padding box drawn with inline
 * styles, so it needs no stylesheet and also works in content loaded later by
 * AJAX. Three things on the iframe are load-bearing:
 *
 *  - allow="… encrypted-media …": DRM playback cannot start without it.
 *  - referrerpolicy="strict-origin-when-cross-origin": Wolvy refuses embeds
 *    that arrive without a Referer and checks it against the account's
 *    allowed domains, even when the site sends `Referrer-Policy: no-referrer`.
 *  - no sandbox attribute: a sandbox blocks DRM.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class player {

    /**
     * Markup for one video, for the user viewing the page.
     *
     * @param string $videoid Wolvy video id, already validated.
     * @param array $atts Tag attributes (aspect, title).
     * @return string HTML; '' when nothing should be shown.
     */
    public static function render(string $videoid, array $atts = []): string {
        $accountid = (int) get_config('filter_wolvy', 'accountid');

        if ($accountid <= 0) {
            // Only someone who can fix it is told; a student sees nothing.
            if (has_capability('moodle/site:config', \context_system::instance())) {
                return \html_writer::div(s(get_string('notconfigured', 'filter_wolvy')), 'filter-wolvy-notice alert alert-warning');
            }
            return '';
        }

        $vt = '';

        if (self::is_guest()) {
            $guests = (string) (get_config('filter_wolvy', 'guests') ?: 'message');

            if ($guests === 'hide') {
                return '';
            }

            if ($guests !== 'unsigned') {
                return self::login_notice();
            }
        } else {
            $vt = self::token_for_current_user();
        }

        return self::iframe(self::embed_url($videoid, $accountid, $vt), $atts);
    }

    /**
     * Not signed in, or signed in as the guest user.
     *
     * @return bool
     */
    public static function is_guest(): bool {
        return !isloggedin() || isguestuser();
    }

    /**
     * A token for the user viewing the page.
     *
     * @return string '' when the site has no signing secret.
     */
    public static function token_for_current_user(): string {
        global $USER;

        $secret = trim((string) get_config('filter_wolvy', 'signingsecret'));
        if ($secret === '') {
            return '';
        }

        $viewerid = viewer::id_for($USER, (string) get_config('filter_wolvy', 'viewerid'));
        $ttl = (int) (get_config('filter_wolvy', 'tokenlifetime') ?: 7200);

        return token::make($viewerid, $ttl, $secret);
    }

    /**
     * {base}/{video id}/{base64 account id}[?vt=…]
     *
     * @param string $videoid Video id.
     * @param int $accountid Wolvy account id.
     * @param string $vt Viewer token, or ''.
     * @return string
     */
    public static function embed_url(string $videoid, int $accountid, string $vt): string {
        $url = text_filter::EMBED_BASE . '/' . rawurlencode($videoid) . '/' . base64_encode((string) $accountid);

        if ($vt !== '') {
            $url .= '?vt=' . rawurlencode($vt);
        }

        return $url;
    }

    /**
     * The iframe in its box.
     *
     * @param string $src Embed URL.
     * @param array $atts Tag attributes.
     * @return string
     */
    protected static function iframe(string $src, array $atts): string {
        $title = trim((string) ($atts['title'] ?? ''));
        if ($title === '') {
            $title = get_string('defaulttitle', 'filter_wolvy');
        }

        $aspect = (string) ($atts['aspect'] ?? '');
        if ($aspect === '') {
            $aspect = (string) (get_config('filter_wolvy', 'aspect') ?: '16:9');
        }

        $box = 'position:relative;width:100%;height:0;overflow:hidden;padding-top:' . self::padding($aspect) . '%;';
        $frame = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';

        return '<div class="filter-wolvy" style="' . $box . '">'
            . '<iframe src="' . s($src) . '" title="' . s($title) . '" style="' . $frame . '"'
            . ' allow="autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen'
            . ' referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe>'
            . '</div>';
    }

    /**
     * What a visitor who is not signed in sees.
     *
     * @return string
     */
    protected static function login_notice(): string {
        $link = \html_writer::link(get_login_url(), get_string('login'));

        $text = s(get_string('loginrequired', 'filter_wolvy'));

        return \html_writer::div($text . ' ' . $link, 'filter-wolvy-notice alert alert-info');
    }

    /**
     * "16:9" to the padding-top percentage that keeps that shape.
     *
     * @param string $aspect "w:h", "w/h" or a single number.
     * @return float Between 25 (wider than 21:9) and 300 (taller than 9:16).
     */
    public static function padding(string $aspect): float {
        $w = 16.0;
        $h = 9.0;

        if (preg_match('~^\s*(\d+(?:\.\d+)?)\s*[:/]\s*(\d+(?:\.\d+)?)\s*$~', $aspect, $m)) {
            $w = (float) $m[1];
            $h = (float) $m[2];
        } else if (is_numeric($aspect) && (float) $aspect > 0) {
            $w = (float) $aspect;
            $h = 1.0;
        }

        if ($w <= 0 || $h <= 0) {
            $w = 16.0;
            $h = 9.0;
        }

        return max(25.0, min(300.0, round($h / $w * 100, 4)));
    }
}
