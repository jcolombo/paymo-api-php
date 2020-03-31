<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/16/20, 9:09 PM
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
 * Class Task
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Task extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Task';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'task';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'tasks';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     * To make an OR limit: 'propA|propB' = ONLY 1 of these. 'propA||propB' = AT LEAST 1 or more of these.
     */
    public const REQUIRED_CREATE = ['name', 'tasklist_id||project_id'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'project_id',
        // Undocumented types set to readonly for now
        'completed_on', 'completed_by', 'cover_file_id', 'price', 'invoiced', 'start_date',
        'recurring_profile_id', // What is a recurring profile object? Not documented?
        'billing_type'
    ];

    /**
     * An array of properties from the readonly array that can be set during creation but not after
     * (This array is checked so long as the resource entity DOES NOT already have an ID set)
     */
    public const CREATEONLY = ['project_id'];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [
        'project' => false,
        'tasklist' => false,
        'user' => false,
        'thread' => false,
        'entries' => true,
        'invoiceitem' => false,
        'workflowstatus' => false
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'name' => 'text',
        'code' => 'text',
        'project_id' => 'resource:project',
        'tasklist_id' => 'resource:tasklist',
        'seq' => 'integer',
        'description' => 'text',
        'complete' => 'boolean',
        'due_date' => 'date',
        'user_id' => 'resource:user',
        'users' => 'collection:users',
        'billable' => 'boolean',
        'flat_billing' => 'boolean',
        'price_per_hour' => 'decimal',
        'budget_hours' => 'decimal',
        'estimated_price' => 'decimal',
        'invoiced' => 'boolean',
        'invoice_item_id' => 'resource:invoiceitem',
        'priority' => 'intEnum:25|50|75|100',
        'status_id' => 'resource:workflowstatus',
        // Undocumented Props
        'completed_on' => 'datetime',
        'completed_by' => 'resource:user',
        'cover_file_id' => 'resource:file',
        'price' => 'decimal',
        'start_date' => 'date',
        'recurring_profile_id' => 'integer', // What is a recurring profile object? Not documented?
        'billing_type' => 'text'
    ];

    /**
     * Allowable operators for list() calls on specific properties
     * Use [prop] = ['=','!='] to allow these only. Use [!prop] = ['like'] to NOT allow these types
     */
    public const WHERE_OPERATIONS = [];

}