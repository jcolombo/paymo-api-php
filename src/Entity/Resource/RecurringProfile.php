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
 * RECURRING PROFILE RESOURCE - PAYMO INVOICE RECURRING AUTOMATION
 * ======================================================================================
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/invoice_recurring_profiles.md
 *
 * This resource class represents a Paymo Invoice Recurring Profile. Recurring profiles
 * allow you to automate invoice generation on a scheduled basis. Paymo automatically
 * creates invoices from recurring profiles daily at 9 AM UTC based on the configured
 * frequency and start date.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Flexible frequency options (weekly, monthly, yearly, etc.)
 * - Automatic invoice generation at scheduled intervals
 * - Support for taxes, discounts, and multiple line items
 * - Auto-send capability to email invoices automatically
 * - Online payment integration support
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique profile identifier (read-only)
 * - title: Invoice title for generated invoices
 * - currency: ISO currency code (required)
 * - frequency: Recurrence interval (required)
 * - start_date: When to start generating invoices (required)
 *
 * Financial Properties:
 * - subtotal: Sum before taxes (read-only)
 * - total: Final amount including taxes (read-only)
 * - tax/tax2: Tax percentages
 * - tax_amount/tax2_amount: Calculated tax amounts (read-only)
 * - discount: Discount percentage
 * - discount_amount: Calculated discount (read-only)
 *
 * Schedule Properties:
 * - occurrences: Maximum number of invoices to create (null = unlimited)
 * - invoices_created: Number of invoices created so far (read-only)
 * - last_created: Date of last invoice creation (read-only)
 *
 * Settings Properties:
 * - autosend: Automatically email invoices to client
 * - pay_online: Enable online payment options
 * - send_attachment: Include PDF attachment in email
 *
 * FREQUENCY VALUES:
 * -----------------
 * - 'w': Weekly
 * - '2w': Bi-weekly (every 2 weeks)
 * - '3w': Every 3 weeks
 * - '4w': Every 4 weeks
 * - 'm': Monthly
 * - '2m': Bi-monthly (every 2 months)
 * - '3m': Quarterly (every 3 months)
 * - '6m': Semi-annually (every 6 months)
 * - 'y': Yearly
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\RecurringProfile;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\RecurringProfileItem;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a monthly recurring profile
 * $profile = new RecurringProfile();
 * $profile->client_id = 12345;
 * $profile->currency = 'USD';
 * $profile->frequency = 'm'; // Monthly
 * $profile->start_date = '2024-01-01';
 * $profile->title = 'Monthly Retainer';
 * $profile->autosend = true;
 * $profile->create($connection);
 *
 * // Add a line item to the recurring profile
 * $item = new RecurringProfileItem();
 * $item->recurring_profile_id = $profile->id;
 * $item->item = 'Monthly Consulting Fee';
 * $item->description = 'Ongoing consulting services';
 * $item->price_unit = 2500.00;
 * $item->quantity = 1;
 * $item->apply_tax = true;
 * $item->create($connection);
 *
 * // Fetch a recurring profile with its items
 * $profile = RecurringProfile::new()->fetch(55555, ['recurringprofileitems', 'client']);
 *
 * // List recurring profiles for a client
 * $profiles = RecurringProfile::list($connection, [
 *     'where' => [
 *         RequestCondition::where('client_id', 12345),
 *     ]
 * ]);
 *
 * // Update a recurring profile
 * $profile = RecurringProfile::new()->fetch(55555);
 * $profile->occurrences = 12; // Limit to 12 invoices
 * $profile->update($connection);
 * ```
 *
 * INVOICE GENERATION:
 * -------------------
 * Invoices from recurring profiles are created daily at 9 AM UTC. Generation occurs when:
 * - Current date >= start_date
 * - invoices_created < occurrences (or occurrences is null for unlimited)
 * - The date aligns with the calculated frequency intervals
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        RecurringProfileItem Line items for recurring profiles
 * @see        Client Client who receives the invoices
 * @see        Invoice Generated invoice instances
 * @see        InvoiceTemplate Template used for invoice styling
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Recurring Profile resource for automated invoice generation.
 *
 * Recurring profiles define schedules for automatic invoice creation.
 * This class provides full CRUD operations and supports related entity
 * includes for comprehensive recurring profile management.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int     $id                      Unique profile ID (read-only)
 * @property int     $client_id               Client to invoice (required)
 * @property string  $currency                ISO currency code (required)
 * @property string  $frequency               Recurrence interval (required)
 * @property string  $start_date              Start generating date (required)
 * @property int     $template_id             Invoice template ID
 * @property string  $title                   Invoice title
 * @property float   $subtotal                Subtotal before tax (read-only)
 * @property float   $total                   Total with tax (read-only)
 * @property float   $tax                     Primary tax percentage
 * @property float   $tax_amount              Primary tax amount (read-only)
 * @property string  $tax_text                Primary tax label
 * @property float   $tax2                    Secondary tax percentage
 * @property float   $tax2_amount             Secondary tax amount (read-only)
 * @property string  $tax2_text               Secondary tax label
 * @property bool    $tax_on_tax              Compound tax calculation
 * @property float   $discount                Discount percentage
 * @property float   $discount_amount         Discount amount (read-only)
 * @property string  $discount_text           Discount label
 * @property int     $occurrences             Max invoices to create (null=unlimited)
 * @property int     $invoices_created        Invoices created so far (read-only)
 * @property string  $last_created            Last creation date (read-only)
 * @property bool    $autosend                Auto-email invoices
 * @property string  $bill_to                 Customer info block
 * @property string  $company_info            Provider info block
 * @property string  $footer                  Invoice footer text
 * @property string  $notes                   Internal notes
 * @property bool    $pay_online              Enable online payments
 * @property bool    $send_attachment         Include PDF in email
 * @property object  $options                 Additional settings
 * @property string  $created_on              Creation timestamp (read-only)
 * @property string  $updated_on              Last update timestamp (read-only)
 */
class RecurringProfile extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Recurring Profile';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'recurringprofile';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'recurringprofiles';

    /**
     * API response JSON key (differs from endpoint path).
     *
     * @override OVERRIDE-009
     * @see OVERRIDES.md#override-009
     *
     * @var string
     */
    public const API_RESPONSE_KEY = 'recurring_profiles';

    /**
     * Properties required when creating a new recurring profile.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['client_id', 'currency', 'frequency', 'start_date'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are calculated by the server or set automatically.
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
        'discount_amount',
        'invoices_created',
        'last_created',
        'language'  // Deprecated - use invoice templates instead
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
        'client'                => false,
        'recurringprofileitems' => true
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
        'id'               => 'integer',
        'created_on'       => 'datetime',
        'updated_on'       => 'datetime',
        'client_id'        => 'resource:client',
        'currency'         => 'text',
        'frequency'        => 'enum:w|2w|3w|4w|m|2m|3m|6m|y',
        'start_date'       => 'date',
        'template_id'      => 'resource:invoicetemplate',
        'title'            => 'text',
        'subtotal'         => 'decimal',
        'total'            => 'decimal',
        'tax'              => 'decimal',
        'tax_amount'       => 'decimal',
        'tax_text'         => 'text',
        'tax2'             => 'decimal',
        'tax2_amount'      => 'decimal',
        'tax2_text'        => 'text',
        'tax_on_tax'       => 'boolean',
        'discount'         => 'decimal',
        'discount_amount'  => 'decimal',
        'discount_text'    => 'text',
        'occurrences'      => 'integer',
        'invoices_created' => 'integer',
        'last_created'     => 'date',
        'autosend'         => 'boolean',
        'bill_to'          => 'text',
        'company_info'     => 'text',
        'footer'           => 'text',
        'notes'            => 'text',
        'pay_online'       => 'boolean',
        'send_attachment'  => 'boolean',
        'options'          => 'object',
        'language'         => 'text'  // Deprecated - use invoice templates instead
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
        'client_id' => ['='],
        'total'     => ['=', '>', '<', '>=', '<=']
    ];
}
