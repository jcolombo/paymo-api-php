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
 * INVOICE RESOURCE - PAYMO INVOICING AND BILLING
 * ======================================================================================
 *
 * This resource class represents a Paymo invoice. Invoices are billing documents
 * sent to clients for work performed, supporting multiple line items, taxes,
 * discounts, payment tracking, and online payment integration.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Multi-status workflow (draft, sent, viewed, paid, void)
 * - Tax and discount support (single and dual tax)
 * - Payment tracking and reminders
 * - PDF generation and online viewing
 * - Template-based formatting
 * - Online payment support
 *
 * INVOICE STATUSES:
 * -----------------
 * - draft: Invoice created but not sent
 * - sent: Invoice sent to client
 * - viewed: Client has viewed the invoice
 * - paid: Invoice fully paid
 * - void: Invoice cancelled/voided
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique invoice identifier (read-only)
 * - number: Invoice number
 * - client_id: Billed client (required)
 * - template_id: Invoice template reference
 * - status: Current invoice status
 * - currency: Currency code (required)
 * - title: Invoice title/subject
 *
 * Date Properties:
 * - date: Invoice date
 * - due_date: Payment due date
 * - delivery_date: Service delivery date
 *
 * Amount Properties (read-only calculated values):
 * - subtotal: Sum before tax/discount
 * - total: Final amount including tax/discount
 * - outstanding: Remaining unpaid amount
 * - tax_amount: First tax amount
 * - tax2_amount: Second tax amount
 * - discount_amount: Discount amount
 *
 * Tax/Discount Configuration:
 * - tax: Tax percentage
 * - tax_text: Tax label
 * - tax2: Second tax percentage
 * - tax2_text: Second tax label
 * - tax_on_tax: Apply second tax on first
 * - discount: Discount percentage
 * - discount_text: Discount label
 *
 * Content Properties:
 * - bill_to: Client billing address
 * - company_info: Company details
 * - footer: Invoice footer text
 * - notes: Additional notes
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Invoice;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new invoice
 * $invoice = new Invoice();
 * $invoice->client_id = 12345;
 * $invoice->currency = 'USD';
 * $invoice->title = 'January Services';
 * $invoice->date = '2024-01-31';
 * $invoice->due_date = '2024-02-15';
 * $invoice->tax = 8.25;
 * $invoice->tax_text = 'Sales Tax';
 * $invoice->notes = 'Thank you for your business!';
 * $invoice->create($connection);
 *
 * // Fetch invoice with line items
 * $invoice = Invoice::fetch($connection, 67890, [
 *     'include' => ['invoiceitems', 'invoicepayments', 'client']
 * ]);
 *
 * // List unpaid invoices
 * $unpaid = Invoice::list($connection, [
 *     'where' => [
 *         RequestCondition::where('status', 'paid', '!='),
 *     ]
 * ]);
 *
 * // Mark invoice as sent
 * $invoice->status = 'sent';
 * $invoice->update($connection);
 *
 * // Get PDF link
 * echo "PDF: " . $invoice->pdf_link;
 * echo "View online: " . $invoice->permalink;
 * ```
 *
 * CURRENCY HANDLING:
 * ------------------
 * Currency is required and should be a valid ISO 4217 code (USD, EUR, GBP, etc.).
 * All amounts are in the specified currency.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        InvoiceItem Invoice line items
 * @see        InvoicePayment Payment records
 * @see        Client Billed client
 * @see        InvoiceTemplate Invoice formatting
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Invoice resource for billing and invoicing operations.
 *
 * Invoices represent billing documents sent to clients. This class provides
 * full CRUD operations and supports related entity includes for line items,
 * payments, and client data.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id              Unique invoice ID (read-only)
 * @property string $number          Invoice number
 * @property int    $client_id       Billed client ID (required)
 * @property int    $template_id     Invoice template ID
 * @property string $status          Status (draft|sent|viewed|paid|void)
 * @property string $currency        Currency code (required)
 * @property string $date            Invoice date (YYYY-MM-DD)
 * @property string $due_date        Payment due date
 * @property float  $subtotal        Subtotal before tax (read-only)
 * @property float  $total           Total amount (read-only)
 * @property float  $tax             Tax percentage
 * @property float  $tax_amount      Tax amount (read-only)
 * @property float  $tax2            Second tax percentage
 * @property float  $tax2_amount     Second tax amount (read-only)
 * @property float  $discount        Discount percentage
 * @property float  $discount_amount Discount amount (read-only)
 * @property bool   $tax_on_tax      Compound tax flag
 * @property string $bill_to         Client billing address
 * @property string $company_info    Company information
 * @property string $footer          Footer text
 * @property string $notes           Additional notes
 * @property float  $outstanding     Unpaid balance
 * @property string $title           Invoice title
 * @property bool   $pay_online      Online payment enabled
 * @property string $permalink       Online view URL (read-only)
 * @property string $pdf_link        PDF download URL (read-only)
 * @property string $created_on      Creation timestamp (read-only)
 * @property string $updated_on      Last update timestamp (read-only)
 */
class Invoice extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Invoice';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'invoice';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'invoices';

    /**
     * Properties required when creating a new invoice.
     *
     * client_id and currency are both required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['client_id', 'currency'];

    /**
     * Properties that cannot be modified via API.
     *
     * Calculated totals, links, and reminder statuses are read-only.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'subtotal',
      'total',
      'tax_amount',
      'tax2_amount',
      'invoice_id',
      'permalink',
      'pdf_link',
      'language', // Deprecated - now in templates
      'reminder_1_sent',
      'reminder_2_sent',
      'reminder_3_sent',
        // Undocumented
      'discount_amount',
      'download_token'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for invoices.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'client'          => false,
      'invoicepayments' => true,
      'invoiceitems'    => true,
      'invoicetemplate' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'              => 'integer',
      'created_on'      => 'datetime',
      'updated_on'      => 'datetime',
      'number'          => 'text',
      'client_id'       => 'resource:client',
      'template_id'     => 'resource:invoicetemplate',
      'status'          => 'enum:draft|sent|viewed|paid|void',
      'currency'        => 'text',
      'date'            => 'date',
      'due_date'        => 'date',
      'subtotal'        => 'decimal',
      'total'           => 'decimal',
      'tax'             => 'decimal',
      'tax_amount'      => 'decimal',
      'tax2'            => 'decimal',
      'tax2_amount'     => 'decimal',
      'discount'        => 'decimal',
      'discount_amount' => 'decimal',
      'tax_on_tax'      => 'boolean',
      'language'        => 'text',  // Deprecated as used by Invoice Templates
      'bill_to'         => 'text',
      'company_info'    => 'text',
      'footer'          => 'text',
      'notes'           => 'text',
      'outstanding'     => 'decimal',
      'tax_text'        => 'text',
      'tax2_text'       => 'text',
      'discount_text'   => 'text',
      'title'           => 'text',
      'pay_online'      => 'boolean',
      'reminder_1_sent' => 'boolean',
      'reminder_2_sent' => 'boolean',
      'reminder_3_sent' => 'boolean',
      'permalink'       => 'url',
      'pdf_link'        => 'url',
        // Undocumented Props
      'download_token'  => 'text',
      'active'          => 'boolean',
      'delivery_date'   => 'date',
      'options'         => 'object',
      'token'           => 'text'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for invoices.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}