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
 * Wolvy viewer tokens (`vt`), signed on the Moodle server.
 *
 * The format is Wolvy's published one, and nothing else:
 *
 *     payload = base64url( json({"vid": <viewer id>, "exp": <unix time>}) )
 *     sig     = base64url( HMAC-SHA256(signing secret, payload) )    raw bytes
 *     vt      = payload "." sig
 *
 * No request to Wolvy is made. The token names the viewer whose id the
 * watermark shows, and expires; it is not tied to one video.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token {
    /** Shortest lifetime Wolvy accepts, in seconds. */
    const MIN_TTL = 60;

    /** Longest lifetime Wolvy accepts, in seconds. */
    const MAX_TTL = 86400;

    /** Longest viewer id Wolvy accepts, in bytes. */
    const MAX_VIEWER_ID_BYTES = 64;

    /**
     * Sign a token that expires at a given time.
     *
     * @param string $viewerid Viewer id, at most 64 bytes.
     * @param int $expires Unix time the token stops being valid.
     * @param string $secret The account's viewer signing secret.
     * @return string The token, or '' when there is no viewer id or no secret.
     */
    public static function sign(string $viewerid, int $expires, string $secret): string {
        if ($viewerid === '' || $secret === '') {
            return '';
        }

        // Unescaped slashes and Unicode give the same bytes as JavaScript's
        // JSON.stringify, so the published test vector matches exactly.
        $json = json_encode(['vid' => $viewerid, 'exp' => $expires], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return '';
        }

        $payload = self::base64url($json);

        return $payload . '.' . self::base64url(hash_hmac('sha256', $payload, $secret, true));
    }

    /**
     * Sign a token that lives for $ttl seconds from now.
     *
     * @param string $viewerid Viewer id.
     * @param int $ttl Lifetime in seconds, clamped to Wolvy's 60 seconds to 24 hours.
     * @param string $secret Signing secret.
     * @return string
     */
    public static function make(string $viewerid, int $ttl, string $secret): string {
        $ttl = max(self::MIN_TTL, min(self::MAX_TTL, $ttl));

        return self::sign($viewerid, time() + $ttl, $secret);
    }

    /**
     * Base64url without padding.
     *
     * @param string $bytes Raw bytes.
     * @return string
     */
    public static function base64url(string $bytes): string {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Does the signer reproduce Wolvy's published test vector?
     *
     * Shown on the settings page, so a broken PHP build (no hash extension,
     * for example) is noticed before viewers are refused.
     *
     * @return bool
     */
    public static function self_test(): bool {
        $expected = 'eyJ2aWQiOiJ1c2VyLTg4MTMiLCJleHAiOjE3OTAwMDAwMDB9.XBNWyMiuLw0y7WlQkzO5s2N-eI8Ltxhs4N0uvDHM4ZM';

        return hash_equals(
            $expected,
            self::sign('user-8813', 1790000000, 'd80d15d09b7c0e56b4d787f6a212ed8bf8d53c761b6df521278f030cb12a7781')
        );
    }

    /**
     * Does a secret look like the 64 hex characters the Wolvy dashboard issues?
     *
     * @param string $secret Secret.
     * @return bool
     */
    public static function secret_looks_valid(string $secret): bool {
        return (bool) preg_match('/^[0-9a-f]{64}$/i', $secret);
    }
}
