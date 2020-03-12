<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/12/20, 12:16 AM
 * .
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * .
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * .
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
 * Class User
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class User extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'User';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'user';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'users';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     */
    public const REQUIRED_CREATE = ['email'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = [
        'id', 'created_on', 'updated_on',
        'image', // Manually process with the ->upload method
        'image_thumb_large', 'image_thumb_medium', 'image_thumb_small',
        'is_online', 'annual_leave_days_number', 'has_submitted_review',
        'menu_shortcut', 'user_hash', 'workflows', 'additional_privileges'
    ];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [
        'comments' => true,
        'discussions' => true,
        'entries' => true,
        'expenses' => true,
        'files' => true,
        'milestones' => true,
        'reports' => true
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'name' => 'text',
        'email' => 'email',
        'type' => 'enum:Admin|Employee',
        'active' => 'boolean',
        'timezone' => 'text',
        'phone' => 'text',
        'skype' => 'text',
        'position' => 'text',
        'workday_hours' => 'decimal',
        'price_per_hour' => 'decimal',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'image' => 'url',
        'image_thumb_large' => 'url',
        'image_thumb_medium' => 'url',
        'image_thumb_small' => 'url',
        'date_format' => 'enum:Y-m-d|d/m/Y|m/d/Y|d.m.Y',
        'time_format' => 'enum:H:i|h:i a',
        'decimal_sep' => 'text',
        'thousands_sep' => 'text',
        'week_start' => 'integer',
        'language' => 'text',
        'theme' => 'text',
        'assigned_projects' => 'collection:projects',
        'managed_projects' => 'collection:projects',
        'is_online' => 'boolean',
        'password' => 'text',
        // Undocumented Props (Treated as Read Only)
        'annual_leave_days_number' => 'integer',
        'has_submitted_review' => 'text',
        'menu_shortcut' => 'array',
        'user_hash' => 'text',
        'workflows' => 'collection:workflows',
        'additional_privileges' => 'array'
    ];

    /**
     * Allowable operators for list() calls on specific properties
     */
    public const WHERE_OPERATIONS = [
        'type' => ['=', '!=', 'in', 'not in']
    ];

}