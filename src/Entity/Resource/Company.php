<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/10/20, 1:32 PM
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
 * Class Company
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Company extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Company';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'company';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'company';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     */
    public const REQUIRED_CREATE = [];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = [
        'id', 'created_on', 'updated_on',
        'image', // Manually process with the ->upload method
        'image_thumb_large', 'image_thumb_medium', 'image_thumb_small',
        'max_users', 'current_users', 'max_projects', 'current_projects',
        'account_type', 'max_invoices', 'current_invoices'
    ];

    /**
     * Valid relationship entities that can be loaded or attached to this entity
     * TRUE = the include is a list of multiple entities. FALSE = a single object is associated with the entity
     */
    public const INCLUDE_TYPES = [];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'name' => 'text',
        'address' => 'text',
        'phone' => 'text',
        'email' => 'email',
        'url' => 'url',
        'fiscal_information' => 'text',
        'country' => 'text',
        'image' => 'url',
        'image_thumb_large' => 'url',
        'image_thumb_medium' => 'url',
        'image_thumb_small' => 'url',
        'timezone' => 'text',
        'default_currency' => 'text',
        'default_price_per_hour' => 'text',
        'apply_tax_to_expenses' => 'text',
        'tax_on_tax' => 'text',
        'currency_position' => 'enum:left|right',
        'next_invoice_number' => 'text',
        'next_estimate_number' => 'text',
        'online_payments' => 'text',
        'date_format' => 'enum:Y-m-d|d/m/Y|m/d/Y|d.m.Y',
        'time_format' => 'enum:H:i|h:i a',
        'decimal_sep' => 'text',
        'thousands_sep' => 'text',
        'week_start' => 'integer',
        'workday_start' => 'text',
        'working_days' => 'array',
        'account_type' => 'enum:free|commercial',
        'max_users' => 'integer',
        'current_users' => 'integer',
        'max_projects' => 'integer',
        'current_projects' => 'integer',
        'max_invoices' => 'integer',
        'current_invoices' => 'integer'
        // Undocumented Props        
    ];

    /**
     * Allowable operators for list() calls on specific properties
     */
    public const WHERE_OPERATIONS = [];

    public static function list($paymo=null) {
        throw new \Exception("Company is a single resource and does not have a collection list");
    }

    public function create($options=[]) {
        throw new \Exception("Company is a single resource and cannot be created via the API");
    }

    public function delete()
    {
        throw new \Exception("Company cannot be deleted through the API");
    }

}