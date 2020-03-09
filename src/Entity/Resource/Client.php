<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 12:09 AM
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
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Class Client
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Client extends AbstractResource
{
    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Client';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'client';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'clients';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = [
        'id',
        'created_on',
        'updated_on',
        'active',
        'image_thumb_large',
        'image_thumb_medium',
        'image_thumb_small',
        'due_interval',
        'additional_privileges'
    ];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [
        'clientcontacts' => true,
        'projects' => true,
        'invoices' => true,
        'recurringprofiles' => true
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'name' => 'text',
        'address' => 'text',
        'city' => 'text',
        'postal_code' => 'text',
        'country' => 'text',
        'state' => 'text',
        'phone' => 'text',
        'fax' => 'text',
        'email' => 'email',
        'website' => 'url',
        'active' => 'boolean',
        'fiscal_information' => 'text',
        'image' => 'url',
        'image_thumb_large' => 'url',
        'image_thumb_medium' => 'url',
        'image_thumb_small' => 'url',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        // Undocumented Props
        'due_interval' => 'integer',
        'additional_privileges' => 'array'
    ];

    /**
     * Allowable operators for list() calls on specific properties
     */
    public const WHERE_OPERATIONS = [
        'active' => ['='],
        'name' => ['=', 'like', 'not like']
    ];
}