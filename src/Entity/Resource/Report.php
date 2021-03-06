<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/19/20, 1:27 PM
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
 * Class Report
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Report extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Report';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'report';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'reports';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     * To make an OR limit: 'propA|propB' = ONLY 1 of these. 'propA||propB' = AT LEAST 1 or more of these.
     */
    public const REQUIRED_CREATE = ['type', 'date_interval||start_date&end_date'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'info', 'content'];

    /**
     * An array of properties from the readonly array that can be set during creation but not after
     * (This array is checked so long as the resource entity DOES NOT already have an ID set)
     */
    public const CREATEONLY = [];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [
        'user' => false,
        'client' => false
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'name' => 'text',
        'user_id' => 'resource:user',
        'type' => 'enum:static|live|temp',
        'start_date' => '?',
        'end_date' => '?',
        'date_interval' => 'enum:today|yesterday|this_month|last_month|this_week|last_week|this_year|last_year|all_time',
        'projects' => 'collection:projects||enum:all|all_active|all_archived||resource:workflowstatus',
        'clients' => 'collection:clients||enum:all|all_active',
        'users' => 'collection:users||enum:all|all_active|all_archived',
        'include' => [
            'days' => 'boolean',
            'clients' => 'boolean',
            'users' => 'boolean',
            'projects' => 'boolean',
            'tasklists' => 'boolean',
            'tasks' => 'boolean',
            'billed' => 'boolean',
            'entries' => 'boolean'
        ],
        'extra' => [
            'exclude_billed_entries' => 'boolean',
            'exclude_unbilled_entries' => 'boolean',
            'exclude_billable_tasks' => 'boolean',
            'exclude_nonbillable_tasks' => 'boolean',
            'exclude_flat_rate_tasks' => 'boolean',
            'exclude_flats' => 'boolean',
            'enable_time_rounding' => 'boolean',
            'rounding_step' => 'integer',
            'display_charts' => 'boolean',
            'display_costs' => 'boolean',
            'display_entries_descriptions' => 'boolean',
            'display_seconds' => 'boolean',
            'display_tasks_codes' => 'boolean',
            'display_tasks_descriptions' => 'boolean',
            'display_tasks_complete_status' => 'boolean',
            'display_tasks_remaining_time_budgets' => 'boolean',
            'display_tasks_time_budget' => 'boolean',
            'display_projects_budgets' => 'boolean',
            'display_projects_codes' => 'boolean',
            'display_projects_descriptions' => 'boolean',
            'display_projects_remaining_budgets' => 'boolean',
            'display_users_positions' => 'boolean',
            'order' => 'array',

        ],
        'info' => 'object',
        'content' => 'object',
        'permalink' => 'url',
        'shared' => 'boolean',
        'share_client_id' => 'resource:client',
        // Undocumented Props
        'active' => 'boolean',
        'share_users_ids' => 'collection:users',
        'invoice_id' => 'resource:invoice',
        'download_token' => 'text'
    ];

    /**
     * Allowable operators for list() calls on specific properties
     * Use [prop] = ['=','!='] to allow these only. Use [!prop] = ['like'] to NOT allow these types
     */
    public const WHERE_OPERATIONS = [
        'info' => null,
        'content' => null,
        'include' => null,
        'extra' => null
    ];

}