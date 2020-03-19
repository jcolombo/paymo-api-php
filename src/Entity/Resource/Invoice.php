<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 8:42 PM
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
 * Class Invoice
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Invoice extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Invoice';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'invoice';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'invoices';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     * To make an OR limit: 'propA|propB' = ONLY 1 of these. 'propA||propB' = AT LEAST 1 or more of these.
     */
    public const REQUIRED_CREATE = ['client_id', 'currency'];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'subtotal', 'total',
        'tax_amount', 'tax2_amount', 'invoice_id', 'permalink', 'pdf_link',
        'language', // Added to readonly since it has been deprecated
        'reminder_1_sent', 'reminder_2_sent', 'reminder_3_sent',
        // Undocumented
        'discount_amount', 'download_token'
    ];

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
        'client' => false,
        'invoicepayments' => true,
        'invoiceitems' => true,
        'invoicetemplate' => false
    ];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'number' => 'text',
        'client_id' => 'resource:client',
        'template_id' => 'resource:invoicetemplate',
        'status' => 'enum:draft|sent|viewed|accepted|invoiced|void',
        'currency' => 'text',
        'date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal',
        'total' => 'decimal',
        'tax' => 'decimal',
        'tax_amount' => 'decimal',
        'tax2' => 'decimal',
        'tax2_amount' => 'decimal',
        'discount' => 'decimal',
        'discount_amount' => 'decimal',
        'tax_on_tax' => 'boolean',
        'language' => 'text',  // Deprecated as used by Invoice Templates
        'bill_to' => 'text',
        'company_info' => 'text',
        'footer' => 'text',
        'notes' => 'text',
        'outstanding' => 'decimal',
        'tax_text' => 'text',
        'tax2_text' => 'text',
        'discount_text' => 'text',
        'title' => 'text',
        'pay_online' => 'boolean',
        'reminder_1_sent' => 'boolean',
        'reminder_2_sent' => 'boolean',
        'reminder_3_sent' => 'boolean',
        'permalink' => 'url',
        'pdf_link' => 'url',
        // Undocumented Props
        'download_token' => 'text',
        'active' => 'boolean',
        'delivery_date' => 'date',
        'options' => 'object',
        'token' => 'text'
    ];

    /**
     * Allowable operators for list() calls on specific properties
     * Use [prop] = ['=','!='] to allow these only. Use [!prop] = ['like'] to NOT allow these types
     */
    public const WHERE_OPERATIONS = [];

}