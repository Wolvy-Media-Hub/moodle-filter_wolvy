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

/**
 * The course page "Wolvy video tags": makes the [wolvy] tag for one video in
 * this course, with the key that lets its players carry a viewer token.
 *
 * Read-only: it changes nothing, so it is a plain GET form.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$input = optional_param('video', '', PARAM_RAW_TRIMMED);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability(\filter_wolvy\tag::CAPABILITY, $context);

$url = new moodle_url('/filter/wolvy/tag.php', ['courseid' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('tagpage', 'filter_wolvy') . ': ' . format_string($course->shortname, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('tagpage', 'filter_wolvy'));
echo html_writer::tag('p', s(get_string('tagpage_intro', 'filter_wolvy')));

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out_omit_querystring(), 'class' => 'mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $course->id]);
echo html_writer::label(get_string('tagvideo', 'filter_wolvy'), 'filter-wolvy-video', true, ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'video',
    'id' => 'filter-wolvy-video',
    'value' => $input,
    'class' => 'form-control mb-2',
    'autocomplete' => 'off',
    'spellcheck' => 'false',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('tagmake', 'filter_wolvy'),
]);
echo html_writer::end_tag('form');

if ($input !== '') {
    $videoid = \filter_wolvy\tag::video_from_input($input);
    if ($videoid === '') {
        echo $OUTPUT->notification(get_string('tagbadvideo', 'filter_wolvy'), \core\output\notification::NOTIFY_ERROR);
    } else {
        $coursename = format_string($course->shortname, true, ['context' => $context]);
        $label = get_string('tagresult', 'filter_wolvy', $coursename);
        echo html_writer::label($label, 'filter-wolvy-tag', true, ['class' => 'form-label']);
        echo html_writer::tag('textarea', s(\filter_wolvy\tag::make($videoid, (int) $course->id)), [
            'id' => 'filter-wolvy-tag',
            'class' => 'form-control',
            'rows' => 2,
            'readonly' => 'readonly',
        ]);
        echo html_writer::tag('p', s(get_string('tagresult_note', 'filter_wolvy')), ['class' => 'form-text text-muted mt-2']);
    }
}

echo $OUTPUT->footer();
