<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 9:11 PM
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
 * INVOICE PAYMENT RESOURCE - PAYMO PAYMENT TRACKING
 * ======================================================================================
 *
 * This resource class represents a Paymo invoice payment. Invoice payments record
 * partial or full payments received against invoices. Multiple payments can be
 * recorded against a single invoice until it's fully paid.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Invoice association (required, locked after creation)
 * - Payment amount tracking
 * - Payment date recording
 * - Notes for payment reference
 *
 * PAYMENT WORKFLOW:
 * -----------------
 * 1. Invoice is created with total amount
 * 2. Client makes payment(s)
 * 3. Payment records are created for each payment received
 * 4. Invoice status updates based on total payments vs invoice amount
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique payment identifier (read-only)
 * - invoice_id: Parent invoice (required, create-only in practice)
 * - amount: Payment amount (required)
 * - date: Payment date
 * - notes: Payment reference/notes
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\InvoicePayment;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Record a payment
 * $payment = new InvoicePayment();
 * $payment->invoice_id = 12345;
 * $payment->amount = 500.00;
 * $payment->date = '2024-01-15';
 * $payment->notes = 'Check #1234';
 * $payment->create($connection);
 *
 * // Fetch payment with invoice details
 * $payment = InvoicePayment::fetch($connection, 67890, [
 *     'include' => ['invoice']
 * ]);
 *
 * // List payments for an invoice
 * $payments = InvoicePayment::list($connection, [
 *     'where' => [
 *         RequestCondition::where('invoice_id', 12345),
 *     ]
 * ]);
 *
 * // Calculate total payments
 * $totalPaid = 0;
 * foreach ($payments as $payment) {
 *     $totalPaid += $payment->amount;
 * }
 *
 * // Update payment notes
 * $payment->notes = 'Check #1234 - Wire transfer confirmed';
 * $payment->update($connection);
 *
 * // Delete payment (if entered incorrectly)
 * $payment->delete();
 * ```
 *
 * INVOICE ID BEHAVIOR:
 * --------------------
 * Note that invoice_id is in READONLY but is required for creation.
 * This means you can set it when creating but cannot change it afterward.
 * This prevents moving payments between invoices.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Invoice Parent invoice
 * @see        Client Invoice client
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo InvoicePayment resource for payment tracking.
 *
 * Invoice payments record partial or full payments received against invoices.
 * This class provides full CRUD operations with invoice association.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id         Unique payment ID (read-only)
 * @property int    $invoice_id Parent invoice ID (required, read-only after create)
 * @property float  $amount     Payment amount (required)
 * @property string $date       Payment date (YYYY-MM-DD)
 * @property string $notes      Payment reference/notes
 * @property string $created_on Creation timestamp (read-only)
 * @property string $updated_on Last update timestamp (read-only)
 */
class InvoicePayment extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Invoice Payment';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'invoicepayment';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'invoicepayments';

    /**
     * Properties required when creating a new invoice payment.
     *
     * Both invoice_id and amount are required to record a payment.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['invoice_id', 'amount'];

    /**
     * Properties that cannot be modified via API.
     *
     * Note: invoice_id is read-only after creation to prevent
     * moving payments between invoices.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'invoice_id'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty - invoice_id behavior is handled by READONLY.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - invoice: Parent invoice (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'invoice' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Amount uses decimal type for currency precision.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'         => 'integer',
      'created_on' => 'datetime',
      'updated_on' => 'datetime',
      'invoice_id' => 'resource:invoice',
      'amount'     => 'decimal',
      'date'       => 'date',
      'notes'      => 'text'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for invoice payments.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}