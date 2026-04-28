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

namespace mod_chat;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/chat/lib.php');

/**
 * Tests for format_message.
 *
 * @package    mod_chat
 * @copyright  2016 Andrew NIcols
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class format_message_test extends \advanced_testcase {
    const USER_CURRENT = 1;
    const USER_OTHER = 2;

    public static function chat_format_message_manually_provider(): array {
        $dateregexp = '\d{2}:\d{2}';

        return [
            'Beep everyone' => [
                'beep all',
                false,
                true,
                "/^{$dateregexp}: " . get_string('messagebeepseveryone', 'chat', '__CURRENTUSER__') . ': /',
                false,
                true,
            ],

            'Beep the current user' => [
                'beep __CURRENTUSER__',
                false,
                true,
                "/^{$dateregexp}: " . get_string('messagebeepsyou', 'chat', '__CURRENTUSER__') . ': /',
                false,
                true,
            ],

            'Beep another user' => [
                'beep __OTHERUSER__',
                false,
                false,
                null,
                null,
                null,
            ],

            'Malformed beep' => [
                'beep',
                false,
                true,
                "/^{$dateregexp} __CURRENTUSER_FIRST__: beep$/",
                false,
                false,
            ],

            '/me says' => [
                '/me writes a test',
                false,
                true,
                "/^{$dateregexp}: \*\*\* __CURRENTUSER_FIRST__ writes a test$/",
                false,
                false,
            ],

            'Invalid command' => [
                '/help',
                false,
                true,
                "/^{$dateregexp} __CURRENTUSER_FIRST__: \/help$/",
                false,
                false,
            ],

            'To user' => [
                'To Bernard:I love tests',
                false,
                true,
                "/^{$dateregexp}: __CURRENTUSER_FIRST__ " . get_string('saidto', 'chat') . " Bernard: I love tests$/",
                false,
                false,
            ],

            'To user trimmed' => [
                'To Bernard: I love tests',
                false,
                true,
                "/^{$dateregexp}: __CURRENTUSER_FIRST__ " . get_string('saidto', 'chat') . " Bernard: I love tests$/",
                false,
                false,
            ],

            'System: enter' => [
                'enter',
                true,
                true,
                "/^{$dateregexp}: " . get_string('messageenter', 'chat', '__CURRENTUSER__') . "$/",
                true,
                false,
            ],

            'System: exit' => [
                'exit',
                true,
                true,
                "/^{$dateregexp}: " . get_string('messageexit', 'chat', '__CURRENTUSER__') . "$/",
                true,
                false,
            ],
        ];
    }

    /**
     * @dataProvider chat_format_message_manually_provider
     *
     * @param string $messagetext
     * @param bool $issystem
     * @param bool $willreturn
     * @param string|null $expecttext
     * @param bool $refreshusers
     * @param bool $expectbeep
     */
    public function test_chat_format_message_manually(
        $messagetext,
        $issystem,
        $willreturn,
        $expecttext,
        $refreshusers,
        $expectbeep
    ): void {

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $currentuser = $this->getDataGenerator()->create_user();
        $this->setUser($currentuser);
        $otheruser = $this->getDataGenerator()->create_user();

        // Replace the message texts.
        // These can't be done in the provider because it runs before the
        // test starts.
        $messagetext = str_replace('__CURRENTUSER__', $currentuser->id, $messagetext);
        $messagetext = str_replace('__OTHERUSER__', $otheruser->id, $messagetext);

        $message = (object) [
            'message'   => $messagetext,
            'timestamp' => time(),
            'issystem'  => $issystem,
        ];

        $result = chat_format_message_manually($message, $course->id, $currentuser, $currentuser);

        if (!$willreturn) {
            $this->assertFalse($result);
        } else {
            $this->assertNotFalse($result);
            if (!empty($expecttext)) {
                $expecttext = str_replace('__CURRENTUSER__', fullname($currentuser), $expecttext);
                $expecttext = str_replace('__CURRENTUSER_FIRST__', $currentuser->firstname, $expecttext);
                $this->assertMatchesRegularExpression($expecttext, $result->text);
            }

            $this->assertEquals($refreshusers, $result->refreshusers);
            $this->assertEquals($expectbeep, $result->beep);
        }
    }
}
