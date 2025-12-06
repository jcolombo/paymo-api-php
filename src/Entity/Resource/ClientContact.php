<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/15/20, 1:42 PM
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
 * CLIENT CONTACT RESOURCE - PAYMO CLIENT CONTACT MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo client contact. Client contacts are
 * individual people associated with a client organization. They can have
 * portal access, contact information, and profile images.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Client association (required)
 * - Contact information management
 * - Profile image with thumbnails
 * - Portal access control
 * - Main contact designation
 *
 * CLIENT PORTAL ACCESS:
 * ---------------------
 * Contacts can be granted portal access to view project information:
 * - access: Enable/disable portal access
 * - password: Set portal login password (write-only)
 * - additional_privileges: Extra permissions
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique contact identifier (read-only)
 * - name: Contact name (required)
 * - client_id: Parent client (required)
 * - email: Email address
 * - position: Job title/position
 * - is_main: Primary contact flag
 *
 * Contact Information:
 * - phone: Office phone
 * - mobile: Mobile phone
 * - fax: Fax number
 * - skype: Skype handle
 * - notes: Contact notes
 *
 * Portal Access:
 * - access: Portal access enabled
 * - password: Portal password (write-only)
 *
 * Image Properties (read-only):
 * - image: Full profile image URL
 * - image_thumb_large: Large thumbnail
 * - image_thumb_medium: Medium thumbnail
 * - image_thumb_small: Small thumbnail
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\ClientContact;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new contact
 * $contact = new ClientContact();
 * $contact->name = 'Jane Smith';
 * $contact->client_id = 12345;
 * $contact->email = 'jane@example.com';
 * $contact->position = 'Project Manager';
 * $contact->phone = '+1-555-123-4567';
 * $contact->is_main = true;
 * $contact->create($connection);
 *
 * // Create contact with portal access
 * $contact = new ClientContact();
 * $contact->name = 'John Doe';
 * $contact->client_id = 12345;
 * $contact->email = 'john@example.com';
 * $contact->access = true;
 * $contact->password = 'secure_password_123';
 * $contact->create($connection);
 *
 * // Fetch contact with client details
 * $contact = ClientContact::fetch($connection, 67890, [
 *     'include' => ['client']
 * ]);
 *
 * // List contacts for a client
 * $contacts = ClientContact::list($connection, [
 *     'where' => [
 *         RequestCondition::where('client_id', 12345),
 *     ]
 * ]);
 *
 * // Update contact information
 * $contact->phone = '+1-555-987-6543';
 * $contact->notes = 'Prefers email contact';
 * $contact->update($connection);
 *
 * // Revoke portal access
 * $contact->access = false;
 * $contact->update($connection);
 * ```
 *
 * PROFILE IMAGES:
 * ---------------
 * Profile images are uploaded separately using the ->upload() method.
 * After upload, the image and thumbnail URLs become available as read-only
 * properties.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Client Parent client organization
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo ClientContact resource for contact person management.
 *
 * Client contacts represent individual people at client organizations.
 * This class provides full CRUD operations with portal access control.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                    Unique contact ID (read-only)
 * @property string $name                  Contact name (required)
 * @property int    $client_id             Parent client ID (required)
 * @property string $email                 Email address
 * @property string $phone                 Office phone
 * @property string $mobile                Mobile phone
 * @property string $fax                   Fax number
 * @property string $skype                 Skype handle
 * @property string $notes                 Contact notes
 * @property string $position              Job title/position
 * @property bool   $is_main               Primary contact flag
 * @property bool   $access                Portal access enabled
 * @property string $password              Portal password (write-only)
 * @property string $image                 Profile image URL (read-only)
 * @property string $image_thumb_large     Large thumbnail URL (read-only)
 * @property string $image_thumb_medium    Medium thumbnail URL (read-only)
 * @property string $image_thumb_small     Small thumbnail URL (read-only)
 * @property array  $additional_privileges Extra permissions (read-only)
 * @property string $created_on            Creation timestamp (read-only)
 * @property string $updated_on            Last update timestamp (read-only)
 */
class ClientContact extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Client Contact';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'clientcontact';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'clientcontacts';

    /**
     * Properties required when creating a new client contact.
     *
     * Both 'name' and 'client_id' are required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'client_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * Image properties are read-only - use ->upload() to change images.
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
        // Undocumented default to readonly
      'additional_privileges'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for client contacts.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - client: Parent client organization (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['client' => false];

    /**
     * Property type definitions for validation and hydration.
     *
     * Note: 'password' is write-only and will not be returned in API responses.
     * Note: 'client_id' is undocumented in Paymo API docs but functional.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                    => 'integer',
      'created_on'            => 'datetime',
      'updated_on'            => 'datetime',
      'name'                  => 'text',
      'email'                 => 'email',
      'mobile'                => 'text',
      'phone'                 => 'text',
      'fax'                   => 'text',
      'skype'                 => 'text',
      'notes'                 => 'text',
      'position'              => 'text',
      'is_main'               => 'boolean',
      'access'                => 'boolean',
      'image'                 => 'url',
      'image_thumb_large'     => 'url',
      'image_thumb_medium'    => 'url',
      'image_thumb_small'     => 'url',
        // Special Props
      'password'              => 'text',     // Will not return with fetches, used in UPDATES and CREATE only
        // Undocumented Props
      'client_id'             => 'resource:client',   // Why is this not documented in the object doc
      'additional_privileges' => 'array'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for client contacts.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}