<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 9:23 PM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * ======================================================================================
 * USER RESOURCE - PAYMO TEAM MEMBER MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo user (team member). Users are individuals
 * who can access and work within a Paymo account, with different permission levels
 * (Admin or Employee), billing rates, and personal preferences.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Three user types: Admin, Employee, and Guest
 * - Personal profile management (contact, timezone, preferences)
 * - Hourly billing rate configuration
 * - Project assignment tracking
 * - Manager privilege management
 * - Localization settings (date/time format, language)
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique user identifier (read-only)
 * - name: User's display name
 * - email: Email address (required for creation)
 * - type: User role (Admin, Employee, or Guest)
 * - active: Whether user account is active
 * - password: Password (write-only, for creation/update)
 *
 * Contact Properties:
 * - phone: Phone number
 * - skype: Skype username
 * - position: Job title/position
 *
 * Work Properties:
 * - workday_hours: Standard hours per workday
 * - price_per_hour: User's hourly billing rate
 * - assigned_projects: Projects user is assigned to
 * - managed_projects: Projects user manages
 *
 * Preference Properties:
 * - timezone: User's timezone
 * - language: UI language
 * - theme: UI theme
 * - date_format: Date display format
 * - time_format: Time display format
 * - decimal_sep: Decimal separator character
 * - thousands_sep: Thousands separator character
 * - week_start: First day of week (0=Sunday, 1=Monday, etc.)
 *
 * Image Properties (read-only):
 * - image: Full profile picture URL
 * - image_thumb_large: Large thumbnail URL
 * - image_thumb_medium: Medium thumbnail URL
 * - image_thumb_small: Small thumbnail URL
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\User;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new team member
 * $user = new User();
 * $user->email = 'newuser@company.com';
 * $user->name = 'John Developer';
 * $user->type = 'Employee';
 * $user->position = 'Software Engineer';
 * $user->price_per_hour = 75.00;
 * $user->workday_hours = 8;
 * $user->timezone = 'America/New_York';
 * $user->password = 'securePassword123';
 * $user->create($connection);
 *
 * // Fetch a user with related data
 * $user = User::fetch($connection, 12345, [
 *     'include' => ['entries', 'expenses', 'comments']
 * ]);
 *
 * // List all admin users
 * $admins = User::list($connection, [
 *     'where' => [
 *         RequestCondition::where('type', 'Admin'),
 *     ]
 * ]);
 *
 * // List active employees
 * $employees = User::list($connection, [
 *     'where' => [
 *         RequestCondition::where('type', 'Employee'),
 *         RequestCondition::where('active', true),
 *     ]
 * ]);
 *
 * // Update user preferences
 * $user->language = 'en';
 * $user->date_format = 'Y-m-d';
 * $user->update($connection);
 *
 * // Change user's billing rate
 * $user->price_per_hour = 85.00;
 * $user->update($connection);
 * ```
 *
 * USER TYPES:
 * -----------
 * - Admin: Full access to all features, can manage other users
 * - Employee: Limited access based on project assignments
 *
 * DATE/TIME FORMATS:
 * ------------------
 * date_format options: 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd.m.Y'
 * time_format options: 'H:i' (24-hour), 'h:i a' (12-hour with AM/PM)
 *
 * IMAGE HANDLING:
 * ---------------
 * Profile images are read-only properties returned from the API.
 * To upload a user image, use the separate upload method.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Project Projects users are assigned to
 * @see        TimeEntry User's time entries
 * @see        Task Tasks assigned to users
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo User resource for team member management operations.
 *
 * Users represent individuals with access to a Paymo account. This class
 * provides full CRUD operations and supports related entity includes for
 * comprehensive user data retrieval.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                  Unique user ID (read-only)
 * @property string $name                User's display name
 * @property string $email               Email address (required)
 * @property string $type                User type (Admin|Employee|Guest)
 * @property bool   $active              Whether user is active
 * @property string $timezone            Timezone identifier
 * @property string $phone               Phone number
 * @property string $skype               Skype username
 * @property string $position            Job title/position
 * @property float  $workday_hours       Standard hours per workday
 * @property float  $price_per_hour      Hourly billing rate
 * @property string $image               Profile picture URL (read-only)
 * @property string $image_thumb_large   Large thumbnail URL (read-only)
 * @property string $image_thumb_medium  Medium thumbnail URL (read-only)
 * @property string $image_thumb_small   Small thumbnail URL (read-only)
 * @property string $date_format         Date format preference
 * @property string $time_format         Time format preference
 * @property string $decimal_sep         Decimal separator
 * @property string $thousands_sep       Thousands separator
 * @property int    $week_start          First day of week
 * @property string $language            UI language code
 * @property string $theme               UI theme name
 * @property array  $assigned_projects   Project IDs assigned to user
 * @property array  $managed_projects    Project IDs user manages
 * @property bool   $is_online           Online status (read-only)
 * @property string $password            Password (write-only)
 * @property string $created_on          Creation timestamp (read-only)
 * @property string $updated_on          Last update timestamp (read-only)
 */
class User extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'User';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'user';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'users';

    /**
     * Properties required when creating a new user.
     *
     * Only 'email' is required to create a user. An invitation email
     * will be sent to the address.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['email'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are set by the server and returned in responses but cannot
     * be included in create or update requests. Image properties require
     * separate upload handling.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'image', // Manually process with the ->upload method
      'image_thumb_large',
      'image_thumb_medium',
      'image_thumb_small',
      'is_online',
      'annual_leave_days_number',
      'has_submitted_review',
      'menu_shortcut',
      'user_hash',
      'workflows',
      'additional_privileges'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for users - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * TRUE indicates a collection (multiple items), FALSE indicates single entity.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'comments'    => true,
      'discussions' => true,
      'entries'     => true,
      'expenses'    => true,
      'files'       => true,
      'milestones'  => true,
      'reports'     => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Defines the data type for each property. Special types:
     * - 'enum:A|B': String enumeration with allowed values
     * - 'email': Email address format
     * - 'url': URL format
     * - 'decimal': Floating point numbers
     * - 'collection:X': Array of entity references
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                       => 'integer',
      'name'                     => 'text',
      'email'                    => 'email',
      'type'                     => 'enum:Admin|Employee|Guest',
      'active'                   => 'boolean',
      'timezone'                 => 'text',
      'phone'                    => 'text',
      'skype'                    => 'text',
      'position'                 => 'text',
      'workday_hours'            => 'decimal',
      'price_per_hour'           => 'decimal',
      'created_on'               => 'datetime',
      'updated_on'               => 'datetime',
      'image'                    => 'url',
      'image_thumb_large'        => 'url',
      'image_thumb_medium'       => 'url',
      'image_thumb_small'        => 'url',
      'date_format'              => 'enum:Y-m-d|d/m/Y|m/d/Y|d.m.Y',
      'time_format'              => 'enum:H:i|h:i a',
      'decimal_sep'              => 'text',
      'thousands_sep'            => 'text',
      'week_start'               => 'integer',
      'language'                 => 'text',
      'theme'                    => 'text',
      'assigned_projects'        => 'collection:projects',
      'managed_projects'         => 'collection:projects',
      'is_online'                => 'boolean',
      'password'                 => 'text',
        // Undocumented Props (Treated as Read Only)
      'annual_leave_days_number' => 'integer',
      'has_submitted_review'     => 'text',
      'menu_shortcut'            => 'array',
      'user_hash'                => 'text',
      'workflows'                => 'collection:workflows',
      'additional_privileges'    => 'array'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Restricts which comparison operators can be used with certain properties.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
      'type' => ['=', '!=', 'in', 'not in']
    ];
}