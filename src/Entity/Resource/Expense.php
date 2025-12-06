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
 * EXPENSE RESOURCE - PAYMO EXPENSE TRACKING
 * ======================================================================================
 *
 * This resource class represents a Paymo expense. Expenses track costs incurred
 * on behalf of clients or projects that can be billed or reimbursed. They support
 * receipt attachments, tagging, and invoice integration.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Client association (required)
 * - Project association (optional)
 * - Receipt file attachments with thumbnails
 * - Invoice integration for billing
 * - Tag categorization
 * - Multi-currency support
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique expense identifier (read-only)
 * - name: Expense name/description
 * - client_id: Associated client (required)
 * - project_id: Associated project (optional)
 * - amount: Expense amount (required)
 * - currency: Currency code (required)
 * - date: Expense date
 * - notes: Additional notes
 * - tags: Categorization tags
 *
 * Receipt Properties (read-only):
 * - file: Receipt file URL
 * - image_thumb_small: Small thumbnail
 * - image_thumb_medium: Medium thumbnail
 * - image_thumb_large: Large thumbnail
 *
 * Billing Properties:
 * - invoiced: Whether expense has been invoiced
 * - invoice_item_id: Associated invoice item (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Expense;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new expense
 * $expense = new Expense();
 * $expense->client_id = 12345;
 * $expense->project_id = 67890;
 * $expense->amount = 150.00;
 * $expense->currency = 'USD';
 * $expense->date = '2024-01-15';
 * $expense->name = 'Software License';
 * $expense->notes = 'Annual subscription renewal';
 * $expense->tags = ['software', 'recurring'];
 * $expense->create($connection);
 *
 * // Fetch an expense with related data
 * $expense = Expense::fetch($connection, 11111, [
 *     'include' => ['client', 'project', 'user']
 * ]);
 *
 * // List expenses for a client
 * $expenses = Expense::list($connection, [
 *     'where' => [
 *         RequestCondition::where('client_id', 12345),
 *     ]
 * ]);
 *
 * // List uninvoiced expenses
 * $uninvoiced = Expense::list($connection, [
 *     'where' => [
 *         RequestCondition::where('invoiced', false),
 *     ]
 * ]);
 *
 * // Update expense
 * $expense->amount = 175.00;
 * $expense->notes = 'Adjusted for tax';
 * $expense->update($connection);
 *
 * // Check if invoiced
 * if ($expense->invoiced && $expense->invoice_item_id) {
 *     echo "Billed on invoice item: " . $expense->invoice_item_id;
 * }
 * ```
 *
 * RECEIPT ATTACHMENTS:
 * --------------------
 * Expenses can have receipt files attached. When a receipt image is uploaded:
 * - file: URL to the original file
 * - image_thumb_*: URLs to generated thumbnails (for images)
 *
 * These are read-only after upload.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Client Associated client
 * @see        Project Associated project
 * @see        InvoiceItem Billing integration
 * @see        User Expense creator
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Expense resource for cost tracking operations.
 *
 * Expenses track costs incurred on behalf of clients or projects.
 * This class provides full CRUD operations with receipt attachments
 * and invoice integration.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                  Unique expense ID (read-only)
 * @property string $name                Expense name/description
 * @property int    $client_id           Associated client ID (required)
 * @property int    $project_id          Associated project ID
 * @property int    $user_id             Creator user ID (read-only)
 * @property float  $amount              Expense amount (required)
 * @property string $currency            Currency code (required)
 * @property string $date                Expense date (YYYY-MM-DD)
 * @property string $notes               Additional notes
 * @property array  $tags                Categorization tags
 * @property bool   $invoiced            Whether expense is billed
 * @property int    $invoice_item_id     Invoice item ID (read-only)
 * @property string $file                Receipt file URL (read-only)
 * @property string $image_thumb_small   Small thumbnail URL (read-only)
 * @property string $image_thumb_medium  Medium thumbnail URL (read-only)
 * @property string $image_thumb_large   Large thumbnail URL (read-only)
 * @property string $created_on          Creation timestamp (read-only)
 * @property string $updated_on          Last update timestamp (read-only)
 */
class Expense extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Expense';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'expense';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'expenses';

    /**
     * Properties required when creating a new expense.
     *
     * client_id, currency, and amount are all required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['client_id', 'currency', 'amount'];

    /**
     * Properties that cannot be modified via API.
     *
     * Receipt file properties and invoice association are read-only.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'image_thumb_small',
      'image_thumb_medium',
      'image_thumb_large',
      'file',
      'invoice_item_id',
        // Undocumented
      'download_token'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for expenses - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - client: Associated client (single)
     * - project: Associated project (single)
     * - user: Creator user (single)
     * - invoiceitems: Billing items (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'client'       => false,
      'project'      => false,
      'user'         => false,
      'invoiceitems' => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Amount is a decimal for currency precision.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                 => 'integer',
      'created_on'         => 'datetime',
      'updated_on'         => 'datetime',
      'client_id'          => 'resource:client',
      'project_id'         => 'resource:project',
      'amount'             => 'decimal',
      'currency'           => 'text',
      'date'               => 'date',
      'notes'              => 'text',
      'invoiced'           => 'boolean',
      'invoice_item_id'    => 'resource:invoiceitem',
      'tags'               => 'array',
      'file'               => 'url',
      'image_thumb_large'  => 'url',
      'image_thumb_medium' => 'url',
      'image_thumb_small'  => 'url',
        // Undocumented Props
      'name'               => 'text',
      'user_id'            => 'resource:user',
      'download_token'     => 'text'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for expenses.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}