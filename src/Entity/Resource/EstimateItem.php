<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/17/20, 8:44 PM
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
 * ESTIMATE ITEM RESOURCE - PAYMO QUOTE LINE ITEMS
 * ======================================================================================
 *
 * This resource class represents a Paymo estimate item. Estimate items are the
 * individual line items on a quote/proposal, representing services or products
 * being offered to the client with pricing details.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Estimate association
 * - Unit pricing and quantity
 * - Tax application settings
 * - Sequence ordering
 * - Description support
 *
 * LINE ITEM CALCULATIONS:
 * -----------------------
 * - Line total = price_unit * quantity
 * - Tax applied based on apply_tax flag and estimate tax settings
 * - Sequence controls display order on estimate
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
 * - estimate_id: Parent estimate
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\EstimateItem;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a service line item
 * $item = new EstimateItem();
 * $item->estimate_id = 12345;
 * $item->item = 'Website Development';
 * $item->description = 'Custom responsive website with CMS integration';
 * $item->price_unit = 150.00;
 * $item->quantity = 40; // Estimated hours
 * $item->apply_tax = true;
 * $item->create($connection);
 *
 * // Create a product line item
 * $item = new EstimateItem();
 * $item->estimate_id = 12345;
 * $item->item = 'Premium Hosting (1 year)';
 * $item->price_unit = 299.00;
 * $item->quantity = 1;
 * $item->apply_tax = false;
 * $item->create($connection);
 *
 * // Fetch item with parent estimate
 * $item = EstimateItem::fetch($connection, 67890, [
 *     'include' => ['estimate']
 * ]);
 *
 * // List items for an estimate
 * $items = EstimateItem::list($connection, [
 *     'where' => [
 *         RequestCondition::where('estimate_id', 12345),
 *     ]
 * ]);
 *
 * // Calculate line total
 * $lineTotal = $item->price_unit * $item->quantity;
 * echo "Line total: $" . number_format($lineTotal, 2);
 *
 * // Update item
 * $item->quantity = 50; // Revised estimate
 * $item->update($connection);
 *
 * // Delete item
 * $item->delete();
 * ```
 *
 * ESTIMATE VS INVOICE ITEMS:
 * --------------------------
 * Estimate items are similar to invoice items but are used for quotes/proposals
 * before work begins. They can be converted to invoice items when the estimate
 * is accepted and an invoice is created.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Estimate Parent estimate/quote
 * @see        InvoiceItem Equivalent for invoices
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo EstimateItem resource for quote line items.
 *
 * Estimate items represent individual line items on quotes/proposals.
 * This class provides full CRUD operations with estimate association.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id          Unique item ID (read-only)
 * @property int    $estimate_id Parent estimate ID
 * @property string $item        Line item name/title (required)
 * @property string $description Detailed description
 * @property float  $price_unit  Unit price
 * @property float  $quantity    Number of units
 * @property bool   $apply_tax   Whether tax applies
 * @property int    $seq         Display order
 * @property string $created_on  Creation timestamp (read-only)
 * @property string $updated_on  Last update timestamp (read-only)
 */
class EstimateItem extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'EstimateItem';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'estimateitem';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'estimateitems';

    /**
     * Properties required when creating a new estimate item.
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
     * Currently empty for estimate items.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - estimate: Parent estimate/quote (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['estimate' => false];

    /**
     * Property type definitions for validation and hydration.
     *
     * price_unit and quantity use decimal for currency/unit precision.
     * Note: estimate_id is undocumented but functional.
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
        // Undocumented Props
      'estimate_id' => 'resource:estimate'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for estimate items.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}