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
 * CLIENT RESOURCE - PAYMO CLIENT/CUSTOMER MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo client (customer). Clients are the entities
 * that projects are billed to, containing contact information, billing details, and
 * associations with projects and invoices.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Complete contact and address information
 * - Client logo/image support
 * - Project and invoice associations
 * - Client contacts management
 * - Fiscal/tax information storage
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique client identifier (read-only)
 * - name: Client/company name (required for creation)
 * - email: Primary email address
 * - phone: Phone number
 * - fax: Fax number
 * - website: Company website URL
 *
 * Address Properties:
 * - address: Street address
 * - city: City name
 * - state: State/province
 * - postal_code: ZIP/postal code
 * - country: Country name
 *
 * Business Properties:
 * - fiscal_information: Tax ID, VAT number, etc.
 * - active: Whether client is active (read-only)
 *
 * Image Properties (read-only):
 * - image: Full-size logo URL
 * - image_thumb_large: Large thumbnail URL
 * - image_thumb_medium: Medium thumbnail URL
 * - image_thumb_small: Small thumbnail URL
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new client
 * $client = new Client();
 * $client->name = 'Acme Corporation';
 * $client->email = 'billing@acme.com';
 * $client->phone = '555-123-4567';
 * $client->address = '123 Business Street';
 * $client->city = 'New York';
 * $client->state = 'NY';
 * $client->postal_code = '10001';
 * $client->country = 'United States';
 * $client->fiscal_information = 'Tax ID: 12-3456789';
 * $client->create($connection);
 *
 * // Fetch a client with related data
 * $client = Client::fetch($connection, 12345, [
 *     'include' => ['projects', 'invoices', 'clientcontacts']
 * ]);
 *
 * // List active clients
 * $clients = Client::list($connection, [
 *     'where' => [
 *         RequestCondition::where('active', true),
 *     ]
 * ]);
 *
 * // Search clients by name
 * $clients = Client::list($connection, [
 *     'where' => [
 *         RequestCondition::where('name', 'Acme%', 'like'),
 *     ]
 * ]);
 *
 * // Update client contact info
 * $client->email = 'new-billing@acme.com';
 * $client->update($connection);
 * ```
 *
 * IMAGE HANDLING:
 * ---------------
 * Client images (logos) are read-only properties returned from the API.
 * To upload a client image, use the separate image upload endpoint.
 * Available thumbnail sizes:
 * - image_thumb_large: Large version
 * - image_thumb_medium: Medium version
 * - image_thumb_small: Small version (avatar size)
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        ClientContact Client contact persons
 * @see        Project Projects associated with clients
 * @see        Invoice Invoices for clients
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Client resource for customer management operations.
 *
 * Clients represent companies or individuals that projects are associated with
 * and invoiced to. This class provides full CRUD operations and supports
 * related entity includes for comprehensive client data retrieval.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                  Unique client ID (read-only)
 * @property string $name                Client/company name (required)
 * @property string $address             Street address
 * @property string $city                City name
 * @property string $postal_code         ZIP/postal code
 * @property string $country             Country name
 * @property string $state               State/province
 * @property string $phone               Phone number
 * @property string $fax                 Fax number
 * @property string $email               Primary email address
 * @property string $website             Company website URL
 * @property bool   $active              Whether client is active (read-only)
 * @property string $fiscal_information  Tax ID, VAT, billing details
 * @property string $image               Full logo URL (read-only)
 * @property string $image_thumb_large   Large thumbnail URL (read-only)
 * @property string $image_thumb_medium  Medium thumbnail URL (read-only)
 * @property string $image_thumb_small   Small thumbnail URL (read-only)
 * @property string $created_on          Creation timestamp (read-only)
 * @property string $updated_on          Last update timestamp (read-only)
 */
class Client extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Client';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'client';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'clients';

    /**
     * Properties required when creating a new client.
     *
     * Only 'name' is required to create a client.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

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
      'active',
      'image', // Manually process with the ->image method
      'image_thumb_large',
      'image_thumb_medium',
      'image_thumb_small',
      'due_interval',
      'additional_privileges'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for clients - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Properties returned by API but cannot be explicitly selected.
     *
     * @override OVERRIDE-013
     * @see OVERRIDES.md#override-013
     *
     * additional_privileges: Internal field, causes HTTP 400 when selected
     * image_thumb_large: Exists in API response but causes HTTP 400 when explicitly selected
     * image_thumb_medium: Exists in API response but causes HTTP 400 when explicitly selected
     * image_thumb_small: Exists in API response but causes HTTP 400 when explicitly selected
     *
     * @var array<string>
     */
    public const UNSELECTABLE = [
        'additional_privileges',
        'image_thumb_large',
        'image_thumb_medium',
        'image_thumb_small',
    ];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * TRUE indicates a collection (multiple items), FALSE indicates single entity.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'clientcontacts'    => true,
      'projects'          => true,
      'invoices'          => true,
      'recurringprofiles' => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Defines the data type for each property:
     * - 'integer': Whole numbers
     * - 'text': String values
     * - 'email': Email address format
     * - 'url': URL format
     * - 'boolean': True/false
     * - 'datetime': ISO 8601 timestamp
     * - 'array': Generic array type
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                    => 'integer',
      'name'                  => 'text',
      'address'               => 'text',
      'city'                  => 'text',
      'postal_code'           => 'text',
      'country'               => 'text',
      'state'                 => 'text',
      'phone'                 => 'text',
      'fax'                   => 'text',
      'email'                 => 'email',
      'website'               => 'url',
      'active'                => 'boolean',
      'fiscal_information'    => 'text',
      'created_on'            => 'datetime',
      'updated_on'            => 'datetime',

      // @override OVERRIDE-001
      // @see OVERRIDES.md#override-001
      // CONDITIONAL PROPERTIES: These are only returned by the API when the client has an
      // image uploaded. When no image exists, these properties are absent from the response.
      // This is expected behavior - not a bug. Use $client->image($filepath) to upload.
      'image'                 => 'url',
      'image_thumb_large'     => 'url',
      'image_thumb_medium'    => 'url',
      'image_thumb_small'     => 'url',

      // Undocumented Props
      'due_interval'          => 'integer',
      'additional_privileges' => 'array'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Restricts which comparison operators can be used with certain properties.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
      'active' => ['='],
      'name'   => ['=', 'like', 'not like']
    ];
}