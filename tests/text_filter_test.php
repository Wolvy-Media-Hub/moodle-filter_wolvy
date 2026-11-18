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
 * Tests for the [wolvy] filter.
 *
 * @package   filter_wolvy
 * @category  test
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_wolvy\text_filter
 * @covers    \filter_wolvy\player
 */
final class text_filter_test extends \advanced_testcase {

    /** Secret of Wolvy's published test vector. */
    const SECRET = 'd80d15d09b7c0e56b4d787f6a212ed8bf8d53c761b6df521278f030cb12a7781';

    /**
     * Configure the filter.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('accountid', 42, 'filter_wolvy');
        set_config('signingsecret', self::SECRET, 'filter_wolvy');
        set_config('viewerid', 'moodle-{id}', 'filter_wolvy');
        set_config('guests', 'message', 'filter_wolvy');
    }

    /**
     * Run the filter on some text.
     *
     * @param string $text Text.
     * @return string
     */
    private function run_filter(string $text): string {
        $filter = new text_filter(\context_system::instance(), []);
        return $filter->filter($text);
    }

    /**
     * The token in an iframe's src, decoded.
     *
     * @param string $html Filter output.
     * @return array|null Payload, or null when there is no token.
     */
    private function token_payload(string $html): ?array {
        if (!preg_match('~\?vt=([A-Za-z0-9_\-.%]+)~', $html, $m)) {
            return null;
        }
        $payload = explode('.', rawurldecode($m[1]))[0];
        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    }

    /**
     * Two students get their own tokens; the iframe keeps what DRM needs.
     */
    public function test_each_student_gets_their_own_token(): void {
        $one = $this->getDataGenerator()->create_user();
        $two = $this->getDataGenerator()->create_user();

        $this->setUser($one);
        $html = $this->run_filter('<p>[wolvy id="a1b2c3d4e5f60718293a"]</p>');
        $this->assertStringContainsString('https://embed.wolvy.stream/a1b2c3d4e5f60718293a/NDI=?vt=', $html);
        $this->assertStringContainsString('encrypted-media', $html);
        $this->assertStringContainsString('referrerpolicy="strict-origin-when-cross-origin"', $html);
        $this->assertStringNotContainsString('sandbox', $html);
        $this->assertStringNotContainsString('<p>', $html);
        $this->assertSame('moodle-' . $one->id, $this->token_payload($html)['vid']);

        $this->setUser($two);
        $html = $this->run_filter('<p>[wolvy id="a1b2c3d4e5f60718293a"]</p>');
        $this->assertSame('moodle-' . $two->id, $this->token_payload($html)['vid']);
    }

    /**
     * Visitors who are not logged in get the configured behaviour, never a token.
     */
    public function test_guests(): void {
        $this->setUser(0);
        $html = $this->run_filter('[wolvy id="a1b2c3d4e5f60718293a"]');
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString(get_string('loginrequired', 'filter_wolvy'), $html);

        set_config('guests', 'unsigned', 'filter_wolvy');
        $html = $this->run_filter('[wolvy id="a1b2c3d4e5f60718293a"]');
        $this->assertStringContainsString('<iframe', $html);
        $this->assertNull($this->token_payload($html));

        set_config('guests', 'hide', 'filter_wolvy');
        $this->assertSame('x  y', $this->run_filter('x [wolvy id="a1b2c3d4e5f60718293a"] y'));

        $this->setGuestUser();
        set_config('guests', 'message', 'filter_wolvy');
        $this->assertStringNotContainsString('<iframe', $this->run_filter('[wolvy id="a1b2c3d4e5f60718293a"]'));
    }

    /**
     * Quotes as text editors write them; invalid ids and plain text are left alone.
     */
    public function test_tag_forms(): void {
        $this->setUser($this->getDataGenerator()->create_user());

        foreach ([
            '[wolvy id="abc123"]',
            "[wolvy id='abc123']",
            '[wolvy id=&quot;abc123&quot;]',
            "[wolvy id=\u{201C}abc123\u{201D}]",
            '[wolvy id=abc123]',
            '[WOLVY ID="abc123"]',
        ] as $tag) {
            $this->assertStringContainsString('embed.wolvy.stream/abc123/', $this->run_filter($tag), $tag);
        }

        $this->assertSame('[wolvy id="no"]', $this->run_filter('[wolvy id="no"]'));
        $this->assertSame('[wolvy id="../etc"]', $this->run_filter('[wolvy id="../etc"]'));
        $this->assertSame('No tag here.', $this->run_filter('No tag here.'));

        $html = $this->run_filter('[wolvy id="abc123" aspect="4:3" title="Lesson <1>"]');
        $this->assertStringContainsString('padding-top:75%', $html);
        $this->assertStringContainsString('title="Lesson &lt;1&gt;"', $html);
    }

    /**
     * Without an account ID nothing is shown, except a hint to administrators.
     */
    public function test_not_configured(): void {
        set_config('accountid', '', 'filter_wolvy');

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame('', $this->run_filter('[wolvy id="abc123"]'));

        $this->setAdminUser();
        $this->assertStringContainsString(get_string('notconfigured', 'filter_wolvy'), $this->run_filter('[wolvy id="abc123"]'));
    }

    /**
     * Without a signing secret the player has no token.
     */
    public function test_no_secret(): void {
        set_config('signingsecret', '', 'filter_wolvy');
        $this->setUser($this->getDataGenerator()->create_user());

        $html = $this->run_filter('[wolvy id="abc123"]');
        $this->assertStringContainsString('<iframe', $html);
        $this->assertNull($this->token_payload($html));
    }
}
