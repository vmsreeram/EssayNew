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

/**
 * Privacy Subsystem implementation for qtype_essaynew.
 *
 * @package    qtype_essaynew
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_essaynew\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\user_preference_provider;
use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for qtype_essaynew implementing user_preference_provider.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This component has data.
        // We need to return default options that have been set a user preferences.
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\user_preference_provider
{

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_user_preference('qtype_essaynew_defaultmark', 'privacy:preference:defaultmark');
        $collection->add_user_preference('qtype_essaynew_responseformat', 'privacy:preference:responseformat');
        $collection->add_user_preference('qtype_essaynew_responserequired', 'privacy:preference:responserequired');
        $collection->add_user_preference('qtype_essaynew_responsefieldlines', 'privacy:preference:responsefieldlines');
        $collection->add_user_preference('qtype_essaynew_attachments', 'privacy:preference:attachments');
        $collection->add_user_preference('qtype_essaynew_attachmentsrequired', 'privacy:preference:attachmentsrequired');
        $collection->add_user_preference('qtype_essaynew_maxbytes', 'privacy:preference:maxbytes');
        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('qtype_essaynew_defaultmark', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:defaultmark', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'defaultmark', $preference, $desc);
        }

        $preference = get_user_preferences('qtype_essaynew_responseformat', null, $userid);
        if (null !== $preference) {
            switch($preference) {
                case 'editor':
                    $stringvalue = get_string('formateditor', 'qtype_essaynew');
                    break;
                case 'editorfilepicker':
                    $stringvalue = get_string('formateditorfilepicker', 'qtype_essaynew');
                    break;
                case 'plain':
                    $stringvalue = get_string('formatplain', 'qtype_essaynew');
                    break;
                case 'monospaced':
                    $stringvalue = get_string('formatmonospaced', 'qtype_essaynew');
                    break;
                case 'noinline':
                    $stringvalue = get_string('formatnoinline', 'qtype_essaynew');
                    break;
                default:
                    $stringvalue = get_string('formateditor', 'qtype_essaynew');
                    break;
            }
            $desc = get_string('privacy:preference:responseformat', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'responseformat', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_essaynew_responserequired', null, $userid);
        if (null !== $preference) {
            if ($preference) {
                $stringvalue = get_string('responseisrequired', 'qtype_essaynew');
            } else {
                $stringvalue = get_string('responsenotrequired', 'qtype_essaynew');
            }
            $desc = get_string('privacy:preference:responserequired', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'responserequired', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_essaynew_responsefieldlines', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:responsefieldlines', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'responsefieldlines',
                    get_string('nlines', 'qtype_essaynew', $preference), $desc);
        }
        $preference = get_user_preferences('qtype_essaynew_attachments', null, $userid);
        if (null !== $preference) {
            if ($preference == 0) {
                $stringvalue = get_string('no');
            } else if ($preference == -1) {
                    $stringvalue = get_string('unlimited');
            } else {
                $stringvalue = $preference;
            }
            $desc = get_string('privacy:preference:attachments', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'attachments', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_essaynew_attachmentsrequired', null, $userid);
        if (null !== $preference) {
            if ($preference == 0) {
                $stringvalue = get_string('attachmentsoptional', 'qtype_essaynew');
            } else {
                $stringvalue = $preference;
            }
            $desc = get_string('privacy:preference:attachmentsrequired', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'attachmentsrequired', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_essaynew_maxbytes', null, $userid);
        if (null !== $preference) {
            switch ($preference) {
                case 52428800:
                    $stringvalue = '50MB';
                    break;
                case 20971520:
                    $stringvalue = '20MB';
                    break;
                case 10485760:
                    $stringvalue = '10MB';
                    break;
                case 5242880:
                    $stringvalue = '5MB';
                    break;
                case 2097152:
                    $stringvalue = '2MB';
                    break;
                case 1048576:
                    $stringvalue = '1MB';
                    break;
                case 512000:
                    $stringvalue = '500KB';
                    break;
                case 102400:
                    $stringvalue = '100KB';
                    break;
                case 51200:
                    $stringvalue = '50KB';
                    break;
                case 10240:
                    $stringvalue = '10KB';
                    break;
                default:
                    $stringvalue = '50MB';
                    break;
            }
            $desc = get_string('privacy:preference:maxbytes', 'qtype_essaynew');
            writer::export_user_preference('qtype_essaynew', 'maxbytes', $stringvalue, $desc);
        }
    }
}
