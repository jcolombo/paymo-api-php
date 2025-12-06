<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
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
 * RECURRING PROFILE ITEM RESOURCE - PAYMO RECURRING INVOICE LINE ITEMS
 * ======================================================================================
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/invoice_recurring_profiles.md
 *
 * This resource class represents a line item within a Paymo Recurring Profile.
 * Line items define the products/services that will appear on each automatically
 * generated invoice. When the recurring profile creates an invoice, these items
 * are copied to the new invoice.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Belongs to a parent recurring profile
 * - Supports item descriptions, pricing, and quantities
 * - Tax application control per item
 * - Sequence ordering for display position
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\RecurringProfileItem;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Add a line item to a recurring profile
 * $item = new RecurringProfileItem();
 * $item->recurring_profile_id = 12345;
 * $item->item = 'Monthly Support';
 * $item->description = 'Technical support and maintenance';
 * $item->price_unit = 500.00;
 * $item->quantity = 1;
 * $item->apply_tax = true;
 * $item->seq = 1;
 * $item->create($connection);
 *
 * // Update an existing item
 * $item = RecurringProfileItem::new()->fetch(55555);
 * $item->price_unit = 550.00;
 * $item->update($connection);
 *
 * // Delete a line item
 * $item = RecurringProfileItem::new()->fetch(55555);
 * $item->delete($connection);
 * ```
 *
 * BULK ITEM MANAGEMENT:
 * ---------------------
 * Items can also be managed through the parent RecurringProfile's 'items' array
 * when updating. See RecurringProfile documentation for details.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        RecurringProfile Parent recurring profile
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Recurring Profile Item resource for recurring invoice line items.
 *
 * Represents individual line items within a recurring invoice profile.
 * Each item will appear on invoices generated from the parent profile.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                     Unique item ID (read-only)
 * @property int    $recurring_profile_id   Parent profile ID (required)
 * @property string $item                   Item name (required)
 * @property string $description            Item description
 * @property float  $price_unit             Unit price (required)
 * @property float  $quantity               Quantity (required)
 * @property bool   $apply_tax              Whether to apply tax
 * @property int    $seq                    Display order position
 * @property string $created_on             Creation timestamp (read-only)
 * @property string $updated_on             Last update timestamp (read-only)
 */
class RecurringProfileItem extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Recurring Profile Item';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'recurringprofileitem';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'recurringprofileitems';

    /**
     * Properties required when creating a new line item.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['recurring_profile_id', 'item', 'price_unit', 'quantity'];

    /**
     * Properties that cannot be modified via API.
     *
     * @var array<string>
     */
    public const READONLY = [
        'id',
        'created_on',
        'updated_on'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * The parent profile relationship is established at creation.
     *
     * @var array<string>
     */
    public const CREATEONLY = ['recurring_profile_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
        'recurringprofile' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
        'id'                   => 'integer',
        'created_on'           => 'datetime',
        'updated_on'           => 'datetime',
        'recurring_profile_id' => 'resource:recurringprofile',
        'item'                 => 'text',
        'description'          => 'text',
        'price_unit'           => 'decimal',
        'quantity'             => 'decimal',
        'apply_tax'            => 'boolean',
        'seq'                  => 'integer'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
        'recurring_profile_id' => ['=']
    ];
}
