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
 * ESTIMATE RESOURCE - PAYMO ESTIMATE/QUOTE MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo estimate (quote/proposal). Estimates are
 * pre-sale documents sent to clients for approval before converting to invoices.
 * They support line items, taxes, discounts, and can be converted to invoices.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Multi-status workflow (draft, sent, viewed, accepted, invoiced, void)
 * - Tax and discount support (single and dual tax)
 * - Line item management
 * - PDF generation and online viewing
 * - Template-based formatting
 * - Invoice conversion tracking
 *
 * ESTIMATE STATUSES:
 * ------------------
 * - draft: Estimate created but not sent
 * - sent: Estimate sent to client
 * - viewed: Client has viewed the estimate
 * - accepted: Client accepted the estimate
 * - invoiced: Estimate converted to invoice
 * - void: Estimate cancelled/voided
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique estimate identifier (read-only)
 * - number: Estimate number
 * - client_id: Target client (required)
 * - template_id: Estimate template reference
 * - status: Current estimate status
 * - currency: Currency code (required)
 * - title: Estimate title/subject
 * - date: Estimate date
 *
 * Amount Properties (read-only calculated values):
 * - subtotal: Sum before tax/discount
 * - total: Final amount including tax/discount
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
 * - footer: Estimate footer text
 * - notes: Additional notes
 * - brief_description: Short description
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Estimate;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new estimate
 * $estimate = new Estimate();
 * $estimate->client_id = 12345;
 * $estimate->currency = 'USD';
 * $estimate->title = 'Website Development Proposal';
 * $estimate->date = '2024-01-31';
 * $estimate->tax = 8.25;
 * $estimate->tax_text = 'Sales Tax';
 * $estimate->notes = 'Valid for 30 days';
 * $estimate->create($connection);
 *
 * // Fetch estimate with line items
 * $estimate = Estimate::fetch($connection, 67890, [
 *     'include' => ['estimateitems', 'client']
 * ]);
 *
 * // List pending estimates
 * $pending = Estimate::list($connection, [
 *     'where' => [
 *         RequestCondition::where('status', 'sent'),
 *     ]
 * ]);
 *
 * // Mark estimate as sent
 * $estimate->status = 'sent';
 * $estimate->update($connection);
 *
 * // Get PDF link
 * echo "PDF: " . $estimate->pdf_link;
 * echo "View online: " . $estimate->permalink;
 *
 * // Check if converted to invoice
 * if ($estimate->invoice_id) {
 *     echo "Converted to invoice: " . $estimate->invoice_id;
 * }
 * ```
 *
 * INVOICE CONVERSION:
 * -------------------
 * When an estimate is accepted and converted to an invoice:
 * - status changes to 'invoiced'
 * - invoice_id is populated with the created invoice ID
 * - The invoice can be fetched via the 'invoice' include
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        EstimateItem Estimate line items
 * @see        Invoice Converted invoices
 * @see        Client Estimate clients
 * @see        EstimateTemplate Estimate formatting
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Estimate resource for quote/proposal operations.
 *
 * Estimates are pre-sale documents for client approval. This class provides
 * full CRUD operations and supports related entity includes for line items,
 * clients, and converted invoices.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                Unique estimate ID (read-only)
 * @property string $number            Estimate number
 * @property int    $client_id         Target client ID (required)
 * @property int    $template_id       Template ID
 * @property string $status            Status (draft|sent|viewed|accepted|invoiced|void)
 * @property string $currency          Currency code (required)
 * @property string $date              Estimate date (YYYY-MM-DD)
 * @property float  $subtotal          Subtotal before tax (read-only)
 * @property float  $total             Total amount (read-only)
 * @property float  $tax               Tax percentage
 * @property float  $tax_amount        Tax amount (read-only)
 * @property float  $tax2              Second tax percentage
 * @property float  $tax2_amount       Second tax amount (read-only)
 * @property float  $discount          Discount percentage
 * @property float  $discount_amount   Discount amount (read-only)
 * @property bool   $tax_on_tax        Compound tax flag
 * @property string $bill_to           Client billing address
 * @property string $company_info      Company information
 * @property string $footer            Footer text
 * @property string $notes             Additional notes
 * @property string $title             Estimate title
 * @property string $brief_description Short description
 * @property int    $invoice_id        Linked invoice ID (read-only)
 * @property string $permalink         Online view URL (read-only)
 * @property string $pdf_link          PDF download URL (read-only)
 * @property string $created_on        Creation timestamp (read-only)
 * @property string $updated_on        Last update timestamp (read-only)
 */
class Estimate extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Estimate';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'estimate';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'estimates';

    /**
     * Properties required when creating a new estimate.
     *
     * Both client_id and currency are required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['client_id', 'currency'];

    /**
     * Properties that cannot be modified via API.
     *
     * Calculated totals, links, and invoice associations are read-only.
     * Language is deprecated (now handled by templates).
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
      'language', // Added to readonly since it has been deprecated
        // Undocumented
      'discount_amount',
      'download_token'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for estimates - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - client: Target client (single)
     * - invoice: Converted invoice (single)
     * - estimateitems: Line items (collection)
     * - estimatetemplate: Formatting template (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'client'           => false,
      'invoice'          => false,
      'estimateitems'    => true,
      'estimatetemplate' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Status has 6 possible values tracking the estimate lifecycle.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                => 'integer',
      'created_on'        => 'datetime',
      'updated_on'        => 'datetime',
      'number'            => 'text',
      'client_id'         => 'resource:client',
      'template_id'       => 'resource:estimatetemplate',
      'status'            => 'enum:draft|sent|viewed|accepted|invoiced|void',
      'currency'          => 'text',
      'date'              => 'date',
      'subtotal'          => 'decimal',
      'total'             => 'decimal',
      'tax'               => 'decimal',
      'tax_amount'        => 'decimal',
      'tax2'              => 'decimal',
      'tax2_amount'       => 'decimal',
      'tax_on_tax'        => 'boolean',
      'language'          => 'text',  // Deprecated as used by Estimate Templates
      'bill_to'           => 'text',
      'company_info'      => 'text',
      'footer'            => 'text',
      'notes'             => 'text',
      'tax_text'          => 'text',
      'tax2_text'         => 'text',
      'title'             => 'text',
      'invoice_id'        => 'resource:invoice',
      'permalink'         => 'url',
      'pdf_link'          => 'url',
        // Undocumented Props
      'brief_description' => 'text',
      'discount'          => 'decimal',
      'discount_amount'   => 'decimal',
      'discount_text'     => 'text',
      'download_token'    => 'text'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for estimates.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}