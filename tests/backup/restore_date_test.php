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

namespace mod_chat\backup;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore date tests.
 *
 * @package    mod_chat
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class restore_date_test extends \restore_date_testcase {
    public function test_restore_dates(): void {
        global $DB;

        [$course, $chat] = $this->create_course_and_module('chat');

        // Create a user and enrol them.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        // Insert a chat message directly.
        $message = (object)[
            'chatid' => $chat->id,
            'userid' => $user->id,
            'groupid' => 0,
            'system' => 0,
            'message' => 'hello!',
            'timestamp' => time(),
        ];

        $message->id = $DB->insert_record('chat_messages', $message);

        $timestamp = 1000;
        $DB->set_field('chat_messages', 'timestamp', $timestamp);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newchat = $DB->get_record('chat', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($chat, $newchat, ['timemodified']);
        $props = ['chattime'];
        $this->assertFieldsRolledForward($chat, $newchat, $props);

        $newmessages = $DB->get_records('chat_messages', ['chatid' => $newchat->id]);

        foreach ($newmessages as $message) {
            $this->assertEquals($timestamp, $message->timestamp);
        }
    }
}
