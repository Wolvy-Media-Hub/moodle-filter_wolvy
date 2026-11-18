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
 * Turns [wolvy id="…"] in any Moodle text into a Wolvy player.
 *
 * Each player carries a viewer token signed on this server for the user
 * viewing the page, so the watermark shows that student. A pasted iframe
 * cannot do that: its URL is the same for everyone.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {

    /** Where Wolvy serves embeds. */
    const EMBED_BASE = 'https://embed.wolvy.stream';

    /** A [wolvy …] tag alone in a paragraph: the paragraph is replaced too. */
    const PARAGRAPH_PATTERN = '~<p\b[^>]*>\s*(?:&nbsp;|\s)*(\[wolvy\b[^\]]*\])(?:&nbsp;|\s)*</p>~iu';

    /** Any [wolvy …] tag. */
    const TAG_PATTERN = '~\[wolvy\b([^\]]*)\]~iu';

    /**
     * Replace every [wolvy …] tag with a player.
     *
     * @param string $text Text to filter.
     * @param array $options Filter options (unused).
     * @return string
     */
    public function filter($text, array $options = []) {
        if (!is_string($text) || $text === '' || stripos($text, '[wolvy') === false) {
            return $text;
        }

        // A block-level player must not end up inside a <p>.
        $replace = function (array $m): string {
            return $this->replace_tag($m);
        };

        $text = preg_replace_callback(self::PARAGRAPH_PATTERN, function (array $m) use ($replace): string {
            return preg_replace_callback(self::TAG_PATTERN, $replace, $m[1]);
        }, $text);

        return preg_replace_callback(self::TAG_PATTERN, $replace, $text);
    }

    /**
     * One tag to its player, or to nothing when it names no valid video.
     *
     * @param array $match Regex match; [1] holds the attributes.
     * @return string
     */
    protected function replace_tag(array $match): string {
        $atts = self::parse_attributes($match[1]);
        $videoid = self::clean_video_id($atts['id'] ?? '');

        if ($videoid === '') {
            return $match[0];
        }

        return player::render($videoid, $atts);
    }

    /**
     * Parse id="…" style attributes.
     *
     * Accepts double, single, HTML-encoded and typographic quotes, because
     * text editors turn quotes into any of these, and bare values.
     *
     * @param string $raw The part between "[wolvy" and "]".
     * @return array Lower-case name => value.
     */
    public static function parse_attributes(string $raw): array {
        $raw = str_replace(['&quot;', '&#34;', '&#039;', '&#39;', '&apos;'], ['"', '"', "'", "'", "'"], $raw);
        $raw = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"], ['"', '"', "'", "'"], $raw);

        $atts = [];
        $pattern = '~([a-zA-Z_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))~u';

        if (preg_match_all($pattern, $raw, $all, PREG_SET_ORDER)) {
            foreach ($all as $m) {
                $value = $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : ($m[4] ?? ''));
                $atts[strtolower($m[1])] = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return $atts;
    }

    /**
     * A Wolvy video id, or '' when the value is not one.
     *
     * @param string $id Candidate.
     * @return string
     */
    public static function clean_video_id(string $id): string {
        $id = trim($id);

        return preg_match('/^[A-Za-z0-9._-]{4,64}$/', $id) ? $id : '';
    }
}
