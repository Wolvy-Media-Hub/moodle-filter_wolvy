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

use filter_wolvy\admin\setting_accountid;

/**
 * Tests for the account ID setting.
 *
 * @package   filter_wolvy
 * @category  test
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_wolvy\admin\setting_accountid
 */
final class setting_accountid_test extends \advanced_testcase {
    /**
     * A number, an embed address or embed code gives the number; anything else is kept for validation to refuse.
     */
    public function test_normalise(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->assertSame('42', setting_accountid::normalise(' 42 '));
        $this->assertSame('42', setting_accountid::normalise('https://embed.wolvy.stream/a1b2c3d4e5f60718293a/NDI='));
        $this->assertSame('42', setting_accountid::normalise('https://embed.wolvy.stream/a1b2c3d4e5f60718293a/NDI%3D'));
        $this->assertSame(
            '12345',
            setting_accountid::normalise('<iframe src="https://embed.wolvy.stream/abc123/MTIzNDU=" width="640"></iframe>')
        );
        $this->assertSame('not a number', setting_accountid::normalise('not a number'));
    }

    /**
     * Saving an embed address stores the number.
     */
    public function test_write_setting(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();

        $setting = new setting_accountid('filter_wolvy/accountid', 'Account', '', '', PARAM_INT);
        $this->assertSame('', $setting->write_setting('https://embed.wolvy.stream/a1b2c3d4e5f60718293a/NDI='));
        $this->assertSame('42', (string) get_config('filter_wolvy', 'accountid'));
        $this->assertNotSame('', $setting->write_setting('not a number'));
    }
}
