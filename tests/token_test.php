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
 * Tests for the viewer token signer and the viewer id.
 *
 * @package   filter_wolvy
 * @category  test
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_wolvy\token
 * @covers    \filter_wolvy\viewer
 */
final class token_test extends \advanced_testcase {
    /** Secret of Wolvy's published test vector (docs.wolvy.net/viewer-tokens/). */
    const SECRET = 'd80d15d09b7c0e56b4d787f6a212ed8bf8d53c761b6df521278f030cb12a7781';

    /**
     * The signer reproduces the published vector byte for byte.
     */
    public function test_published_vector(): void {
        $this->assertSame(
            'eyJ2aWQiOiJ1c2VyLTg4MTMiLCJleHAiOjE3OTAwMDAwMDB9.XBNWyMiuLw0y7WlQkzO5s2N-eI8Ltxhs4N0uvDHM4ZM',
            token::sign('user-8813', 1790000000, self::SECRET)
        );
        $this->assertTrue(token::self_test());
    }

    /**
     * A token Wolvy's verifier would accept: payload, signature, expiry.
     */
    public function test_made_token_verifies(): void {
        $vt = token::make('moodle-42', 7200, self::SECRET);
        [$payload, $sig] = explode('.', $vt);

        $this->assertSame(
            token::base64url(hash_hmac('sha256', $payload, self::SECRET, true)),
            $sig
        );

        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $this->assertSame('moodle-42', $data['vid']);
        $this->assertEqualsWithDelta(time() + 7200, $data['exp'], 5);
    }

    /**
     * Lifetimes are clamped to what Wolvy accepts; no secret or viewer gives no token.
     */
    public function test_limits(): void {
        $exp = static function (string $vt): int {
            $payload = explode('.', $vt)[0];
            return json_decode(base64_decode(strtr($payload, '-_', '+/')), true)['exp'];
        };

        $this->assertEqualsWithDelta(time() + 86400, $exp(token::make('a', 999999, self::SECRET)), 5);
        $this->assertEqualsWithDelta(time() + 60, $exp(token::make('a', 1, self::SECRET)), 5);
        $this->assertSame('', token::make('a', 3600, ''));
        $this->assertSame('', token::make('', 3600, self::SECRET));
    }

    /**
     * The template, its fallback, and the 64-byte cut.
     */
    public function test_viewer_id(): void {
        $user = (object) ['id' => 42, 'username' => 'sara', 'email' => 'sara@example.com', 'idnumber' => ''];

        $this->assertSame('moodle-42', viewer::id_for($user, ''));
        $this->assertSame('sara@example.com', viewer::id_for($user, '{email}'));
        $this->assertSame('42-sara', viewer::id_for($user, '{id}-{username}'));

        // An empty ID number, or a template naming no field, falls back.
        $this->assertSame('moodle-42', viewer::id_for($user, '{idnumber}'));
        $this->assertSame('moodle-42', viewer::id_for($user, 'student-{idnumber}'));
        $this->assertSame('moodle-42', viewer::id_for($user, 'everyone'));

        $this->assertSame('', viewer::id_for((object) ['id' => 0], '{id}'));

        $long = viewer::id_for((object) ['id' => 7, 'username' => str_repeat('محمد', 12)], '{username}');
        $this->assertLessThanOrEqual(64, strlen($long));
        $this->assertTrue(mb_check_encoding($long, 'UTF-8'));
    }
}
