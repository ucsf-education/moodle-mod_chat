<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('NO_MOODLE_COOKIES', true); // Session not used here.

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/chat/lib.php');

$chatsid = required_param('chat_sid', PARAM_ALPHANUM);
$chatid   = required_param('chat_id', PARAM_INT);

if (!$chatuser = $DB->get_record('chat_users', ['sid' => $chatsid])) {
    throw new \moodle_exception('notlogged', 'chat');
}
if (!$chat = $DB->get_record('chat', ['id' => $chatid])) {
    throw new \moodle_exception('invalidid', 'chat');
}

if (!$course = $DB->get_record('course', ['id' => $chat->course])) {
    throw new \moodle_exception('invalidcourseid');
}

if (!$cm = get_coursemodule_from_instance('chat', $chat->id, $course->id)) {
    throw new \moodle_exception('invalidcoursemodule');
}

$PAGE->set_url('/mod/chat/gui_header_js/chatinput.php', ['chat_sid' => $chatsid, 'chat_id' => $chatid]);
$PAGE->set_popup_notification_allowed(false);

// Get the user theme.
$USER = $DB->get_record('user', ['id' => $chatuser->userid]);

$module = [
    'name'      => 'mod_chat_header',
    'fullpath'  => '/mod/chat/gui_header_js/module.js',
    'requires'  => ['node'],
];
$PAGE->requires->js_init_call('M.mod_chat_header.init_input', [false], false, $module);

// Setup course, lang and theme.
$PAGE->set_course($course);
$PAGE->set_pagelayout('embedded');
$PAGE->set_focuscontrol('input_chat_message');
$PAGE->set_cacheable(false);
echo $OUTPUT->header();

echo html_writer::start_tag('form', ['action' => '../empty.php',
                                          'method' => 'post',
                                          'target' => 'empty',
                                          'id' => 'inputForm',
                                          'style' => 'margin:0']);
echo html_writer::label(get_string('entermessage', 'chat'), 'input_chat_message', false, ['class' => 'accesshide']);
echo html_writer::empty_tag('input', ['type' => 'text',
                                           'id' => 'input_chat_message',
                                           'name' => 'chat_message',
                                           'size' => '50',
                                           'value' => '']);
echo html_writer::empty_tag('input', ['type' => 'checkbox', 'id' => 'auto', 'checked' => 'checked', 'value' => '']);
echo html_writer::tag('label', get_string('autoscroll', 'chat'), ['for' => 'auto']);
echo html_writer::end_tag('form');

echo html_writer::start_tag('form', ['action' => 'insert.php', 'method' => 'post', 'target' => 'empty', 'id' => 'sendForm']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'chat_sid', 'value' => $chatsid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'chat_message', 'id' => 'insert_chat_message']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
