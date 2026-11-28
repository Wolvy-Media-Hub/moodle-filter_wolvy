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
 * Tests for the keys that let a [wolvy] tag carry a viewer token.
 *
 * @package   filter_wolvy
 * @category  test
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_wolvy\tag
 */
final class tag_test extends \advanced_testcase {
    /**
     * A key belongs to one video in one course.
     */
    public function test_key_is_per_video_and_course(): void {
        $this->resetAfterTest();
        set_config('tagkey', str_repeat('ab', 32), 'filter_wolvy');

        $key = tag::key('a1b2c3d4e5f60718293a', 7);
        $this->assertSame(tag::KEY_LENGTH, strlen($key));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key);
        $this->assertSame($key, tag::key('a1b2c3d4e5f60718293a', 7));

        $this->assertTrue(tag::verify('a1b2c3d4e5f60718293a', 7, $key));
        $this->assertFalse(tag::verify('a1b2c3d4e5f60718293a', 8, $key), 'another course');
        $this->assertFalse(tag::verify('Lesson_2-b', 7, $key), 'another video');
        $this->assertFalse(tag::verify('a1b2c3d4e5f60718293a', 0, $key), 'text outside a course');
        $this->assertFalse(tag::verify('a1b2c3d4e5f60718293a', 7, ''), 'no key');
        $this->assertFalse(tag::verify('a1b2c3d4e5f60718293a', 7, substr($key, 0, -1) . 'A'), 'changed key');

        // Another site, another key: a tag copied between sites carries no token.
        set_config('tagkey', str_repeat('cd', 32), 'filter_wolvy');
        $this->assertFalse(tag::verify('a1b2c3d4e5f60718293a', 7, $key));
    }

    /**
     * The site key is made once and then kept.
     */
    public function test_site_key(): void {
        $this->resetAfterTest();
        unset_config('tagkey', 'filter_wolvy');

        $key = tag::site_key();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key);
        $this->assertSame($key, tag::site_key());
        $this->assertSame($key, get_config('filter_wolvy', 'tagkey'));
    }

    /**
     * The course of a context: the course itself, an activity or block in it, or none.
     */
    public function test_course_of(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $this->assertSame((int) $course->id, tag::course_of(\context_course::instance($course->id)));
        $this->assertSame((int) $course->id, tag::course_of(\context_module::instance($page->cmid)));
        $this->assertSame(0, tag::course_of(\context_user::instance($user->id)));
        $this->assertSame(0, tag::course_of(\context_system::instance()));
    }

    /**
     * What a teacher pastes: an id, an embed address or embed code.
     */
    public function test_video_from_input(): void {
        $this->assertSame('a1b2c3d4e5f60718293a', tag::video_from_input('a1b2c3d4e5f60718293a'));
        $this->assertSame('a1b2c3d4e5f60718293a', tag::video_from_input(' https://embed.wolvy.stream/a1b2c3d4e5f60718293a/NDI= '));
        $this->assertSame(
            'a1b2c3d4e5f60718293a',
            tag::video_from_input('<iframe src="https://embed.wolvy.stream/a1b2c3d4e5f60718293a/NDI=?vt=x"></iframe>')
        );
        $this->assertSame('', tag::video_from_input('no'));
        $this->assertSame('', tag::video_from_input('https://example.com/a1b2c3d4e5f60718293a'));
        $this->assertSame('', tag::video_from_input('../etc/passwd'));
    }

    /**
     * The tag a teacher copies.
     */
    public function test_make(): void {
        $this->resetAfterTest();
        $tag = tag::make('a1b2c3d4e5f60718293a', 7);
        $this->assertSame('[wolvy id="a1b2c3d4e5f60718293a" key="' . tag::key('a1b2c3d4e5f60718293a', 7) . '"]', $tag);
        $atts = text_filter::parse_attributes(substr($tag, 6, -1));
        $this->assertTrue(tag::verify($atts['id'], 7, $atts['key']));
    }
}
