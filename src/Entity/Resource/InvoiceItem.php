<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 8:42 PM
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
 * INVOICE ITEM RESOURCE - PAYMO INVOICE LINE ITEMS
 * ======================================================================================
 *
 * This resource class represents a Paymo invoice item. Invoice items are the
 * individual line items on an invoice, representing services, products, time
 * entries, or expenses being billed to the client.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Invoice association
 * - Unit pricing and quantity
 * - Tax application settings
 * - Expense linking for billable expenses
 * - Time entry aggregation
 * - Sequence ordering
 *
 * LINE ITEM CALCULATIONS:
 * -----------------------
 * - Line total = price_unit * quantity
 * - Tax applied based on apply_tax flag and invoice tax settings
 * - Sequence controls display order on invoice
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique item identifier (read-only)
 * - item: Line item name/title (required)
 * - description: Detailed description
 * - price_unit: Unit price
 * - quantity: Number of units
 * - apply_tax: Whether tax applies
 * - seq: Display order
 *
 * Associations:
 * - invoice_id: Parent invoice
 * - expense_id: Linked expense (for billable expenses)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceItem;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a service line item
 * $item = new InvoiceItem();
 * $item->invoice_id = 12345;
 * $item->item = 'Website Development';
 * $item->description = 'Custom responsive website development';
 * $item->price_unit = 150.00;
 * $item->quantity = 40; // 40 hours
 * $item->apply_tax = true;
 * $item->create($connection);
 *
 * // Create a product line item
 * $item = new InvoiceItem();
 * $item->invoice_id = 12345;
 * $item->item = 'Domain Registration';
 * $item->price_unit = 15.00;
 * $item->quantity = 1;
 * $item->apply_tax = false;
 * $item->create($connection);
 *
 * // Fetch item with related time entries
 * $item = InvoiceItem::fetch($connection, 67890, [
 *     'include' => ['invoice', 'entries', 'tasks']
 * ]);
 *
 * // List items for an invoice
 * $items = InvoiceItem::list($connection, [
 *     'where' => [
 *         RequestCondition::where('invoice_id', 12345),
 *     ]
 * ]);
 *
 * // Calculate line total
 * $lineTotal = $item->price_unit * $item->quantity;
 * echo "Line total: $" . number_format($lineTotal, 2);
 *
 * // Update item
 * $item->quantity = 45; // Adjusted hours
 * $item->update($connection);
 *
 * // Delete item
 * $item->delete();
 * ```
 *
 * EXPENSE LINKING:
 * ----------------
 * When expense_id is set, the item represents a billable expense being
 * passed through to the client. This links to the Expense resource.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Invoice Parent invoice
 * @see        Expense Linked expenses
 * @see        TimeEntry Time entries for the item
 * @see        Task Tasks associated with item
 * @see        Project Projects associated with item
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo InvoiceItem resource for invoice line items.
 *
 * Invoice items represent individual billable line items on invoices.
 * This class provides full CRUD operations with expense and time entry linking.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id          Unique item ID (read-only)
 * @property int    $invoice_id  Parent invoice ID
 * @property string $item        Line item name/title (required)
 * @property string $description Detailed description
 * @property float  $price_unit  Unit price
 * @property float  $quantity    Number of units
 * @property bool   $apply_tax   Whether tax applies
 * @property int    $seq         Display order
 * @property int    $expense_id  Linked expense ID
 * @property string $created_on  Creation timestamp (read-only)
 * @property string $updated_on  Last update timestamp (read-only)
 */
class InvoiceItem extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Invoice Item';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'invoiceitem';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'invoiceitems';

    /**
     * Properties required when creating a new invoice item.
     *
     * Only 'item' (the line item title) is strictly required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['item'];

    /**
     * Properties that cannot be modified via API.
     *
     * Standard timestamp fields are read-only.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for invoice items.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - invoice: Parent invoice (single)
     * - expense: Linked expense (single)
     * - entries: Associated time entries (collection)
     * - projects: Related projects (collection)
     * - tasks: Related tasks (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'invoice'  => false,
      'entries'  => true,
      'expense'  => false,
      'projects' => true,
      'tasks'    => true,
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * price_unit and quantity use decimal for currency/unit precision.
     * Note: invoice_id is undocumented but functional.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'          => 'integer',
      'created_on'  => 'datetime',
      'updated_on'  => 'datetime',
      'item'        => 'text',
      'description' => 'text',
      'price_unit'  => 'decimal',
      'quantity'    => 'decimal',
      'apply_tax'   => 'boolean',
      'seq'         => 'integer',
      'expense_id'  => 'resource:expense',
      'entries'     => 'array',   // Array of entry IDs to mark as billed
        // Undocumented Props
      'invoice_id'  => 'resource:invoice'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for invoice items.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}