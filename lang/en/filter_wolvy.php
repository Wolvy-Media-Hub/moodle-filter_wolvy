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
 * English strings for filter_wolvy.
 *
 * @package   filter_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accountid'] = 'Wolvy account ID';
$string['accountid_desc'] = 'The number of your Wolvy account, shown in the Wolvy dashboard. Every player address ends with it, so a wrong value gives players that fail to load.';
$string['aspect'] = 'Default shape';
$string['aspect_desc'] = 'Width to height of the player when the tag does not say, for example [wolvy id="…" aspect="4:3"].';
$string['defaulttitle'] = 'Wolvy video';
$string['filtername'] = 'Wolvy video';
$string['guests'] = 'Visitors who are not logged in';
$string['guests_desc'] = 'What a visitor who is not logged in, or is logged in as guest, sees instead of a player. A player without a viewer token plays only if your Wolvy account does not require one, and its watermark cannot name the viewer.';
$string['guests_hide'] = 'Nothing';
$string['guests_message'] = 'A message asking them to log in';
$string['guests_unsigned'] = 'The player, without a viewer token';
$string['loginrequired'] = 'Log in to watch this video.';
$string['notconfigured'] = 'Wolvy video: add your Wolvy account ID under Site administration > Plugins > Filters > Wolvy video. Only administrators see this message.';
$string['pluginname'] = 'Wolvy video';
$string['privacy:metadata:wolvy'] = 'To play a protected video, the player loads from Wolvy (wolvy.net) with a viewer token signed by this site. The token carries a viewer ID for the user watching, which Wolvy shows on the video watermark and records with each viewing session.';
$string['privacy:metadata:wolvy:viewerid'] = 'The viewer ID, built from the template in the filter settings (by default the Moodle user ID; the username, email address or ID number if the administrator chose them).';
$string['signingsecret'] = 'Viewer signing secret';
$string['signingsecret_desc'] = 'The 64-character secret from the Wolvy dashboard. With it, each player carries a token signed for the user watching, so the watermark shows that user and Wolvy can refuse anyone without a valid token. Without it, players carry no token. Moodle stores this setting in its database; to keep it out of the database, set $CFG->forced_plugin_settings[\'filter_wolvy\'][\'signingsecret\'] in config.php instead.';
$string['status'] = 'Status';
$string['status_account_missing'] = 'Account ID: not set. No player is shown until it is.';
$string['status_account_ok'] = 'Account ID: set.';
$string['status_domain'] = 'If your Wolvy account allows only some domains, add {$a} to them, or players on this site are refused.';
$string['status_secret_missing'] = 'Signing secret: not set. Players carry no viewer token, so the watermark cannot name the student.';
$string['status_secret_odd'] = 'Signing secret: set, but it is not 64 hexadecimal characters as the Wolvy dashboard issues it. Check that it was copied in full.';
$string['status_secret_ok'] = 'Signing secret: set.';
$string['status_signer_fail'] = 'Token signing: this server does not reproduce Wolvy\'s published test token. Check that PHP has the hash and JSON extensions.';
$string['status_signer_ok'] = 'Token signing: matches Wolvy\'s published test token.';
$string['tokenlifetime'] = 'Token lifetime';
$string['tokenlifetime_desc'] = 'How long a viewer token stays valid after the page is opened. It must outlast the longest video a student watches in one sitting. Wolvy accepts up to 24 hours.';
$string['viewerid'] = 'Viewer ID template';
$string['viewerid_desc'] = 'What identifies the student on the watermark and in Wolvy\'s viewing sessions. {id} is the Moodle user ID, {username} the username, {email} the email address and {idnumber} the ID number. The result is cut to 64 bytes. If it comes out empty for a user, moodle-{id} is used.';
