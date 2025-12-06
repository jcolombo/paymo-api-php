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
 * COMPANY RESOURCE - PAYMO ACCOUNT SETTINGS (SINGLETON)
 * ======================================================================================
 *
 * This resource class represents the Paymo company/account settings. Unlike other
 * resources, Company is a SINGLETON - there is only one company record per Paymo
 * account. This means list(), create(), and delete() operations are not supported.
 *
 * SINGLETON BEHAVIOR:
 * -------------------
 * This is a special resource with restricted operations:
 * - fetch(): Supported - retrieve company settings
 * - update(): Supported - modify company settings
 * - list(): NOT SUPPORTED - throws Exception
 * - create(): NOT SUPPORTED - throws Exception
 * - delete(): NOT SUPPORTED - throws Exception
 *
 * KEY FEATURES:
 * -------------
 * - Account configuration and branding
 * - Company contact information
 * - Default billing and invoicing settings
 * - Timezone and localization preferences
 * - Account limits and usage tracking
 * - Online payment gateway configuration
 * - Email template customization
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Company Information:
 * - id: Unique company identifier (read-only)
 * - name: Company name
 * - address: Street address
 * - phone: Phone number
 * - email: Company email
 * - url: Company website
 * - fiscal_information: Tax/fiscal details
 * - country: Country code
 *
 * Branding (read-only, use upload method):
 * - image: Full company logo URL
 * - image_thumb_large: Large logo thumbnail
 * - image_thumb_medium: Medium logo thumbnail
 * - image_thumb_small: Small logo thumbnail
 *
 * Default Settings:
 * - timezone: Account timezone
 * - default_currency: Default currency code
 * - default_price_per_hour: Default hourly rate
 * - date_format: Date display format
 * - time_format: Time display format
 * - decimal_sep: Decimal separator
 * - thousands_sep: Thousands separator
 * - week_start: First day of week (0-6)
 *
 * Account Limits (read-only):
 * - account_type: Account type (free|commercial)
 * - max_users/current_users: User limits
 * - max_projects/current_projects: Project limits
 * - max_invoices/current_invoices: Invoice limits
 *
 * Invoice/Estimate Settings:
 * - next_invoice_number: Next invoice number
 * - next_estimate_number: Next estimate number
 * - apply_tax_to_expenses: Tax expense setting
 * - tax_on_tax: Compound tax setting
 * - currency_position: Currency symbol position
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Company;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Fetch company settings (no ID needed - singleton)
 * $company = Company::fetch($connection, null);
 * // Or with ID if known
 * $company = Company::fetch($connection, 12345);
 *
 * // Display company info
 * echo "Company: " . $company->name . "\n";
 * echo "Timezone: " . $company->timezone . "\n";
 * echo "Currency: " . $company->default_currency . "\n";
 *
 * // Check account limits
 * echo "Users: " . $company->current_users . "/" . $company->max_users . "\n";
 * echo "Projects: " . $company->current_projects . "/" . $company->max_projects . "\n";
 *
 * // Update company settings
 * $company->name = 'Updated Company Name';
 * $company->timezone = 'America/New_York';
 * $company->default_currency = 'USD';
 * $company->update($connection);
 *
 * // These operations will throw exceptions:
 * // Company::list($connection);  // Exception: singleton
 * // $company->create($connection); // Exception: cannot create
 * // $company->delete();  // Exception: cannot delete
 * ```
 *
 * PAYMENT GATEWAY CONFIGURATION:
 * ------------------------------
 * The company resource contains configuration for online payment providers:
 * - PayPal: op_paypal_email
 * - Stripe: op_stripe_publishable_key, op_stripe_secret_key
 * - Authorize.net: op_authorize_login, cc type flags
 * - PayFlow Pro: op_payflowpro_partner, op_payflowpro_user, op_payflowpro_vendor
 *
 * Note: These properties are mostly read-only through the API.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use RuntimeException;

/**
 * Paymo Company resource for account settings management (Singleton).
 *
 * The Company resource represents the Paymo account configuration. This is
 * a singleton resource - only one exists per account - so list(), create(),
 * and delete() operations throw exceptions.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                     Unique company ID (read-only)
 * @property string $name                   Company name
 * @property string $address                Street address
 * @property string $phone                  Phone number
 * @property string $email                  Company email
 * @property string $url                    Company website
 * @property string $fiscal_information     Tax/fiscal details
 * @property string $country                Country code
 * @property string $image                  Logo URL (read-only)
 * @property string $image_thumb_large      Large thumbnail (read-only)
 * @property string $image_thumb_medium     Medium thumbnail (read-only)
 * @property string $image_thumb_small      Small thumbnail (read-only)
 * @property string $timezone               Account timezone
 * @property string $default_currency       Default currency code
 * @property string $default_price_per_hour Default hourly rate
 * @property string $apply_tax_to_expenses  Tax expense setting
 * @property string $tax_on_tax             Compound tax setting
 * @property string $currency_position      Currency position (left|right)
 * @property string $next_invoice_number    Next invoice number
 * @property string $next_estimate_number   Next estimate number
 * @property string $online_payments        Online payment status
 * @property string $date_format            Date format (Y-m-d, etc.)
 * @property string $time_format            Time format (H:i, h:i a)
 * @property string $decimal_sep            Decimal separator
 * @property string $thousands_sep          Thousands separator
 * @property int    $week_start             First day of week (0-6)
 * @property string $workday_start          Workday start time
 * @property array  $working_days           Working days array
 * @property string $account_type           Account type (read-only)
 * @property int    $max_users              Max users allowed (read-only)
 * @property int    $current_users          Current user count (read-only)
 * @property int    $max_projects           Max projects allowed (read-only)
 * @property int    $current_projects       Current project count (read-only)
 * @property int    $max_invoices           Max invoices allowed (read-only)
 * @property int    $current_invoices       Current invoice count (read-only)
 * @property string $created_on             Creation timestamp (read-only)
 * @property string $updated_on             Last update timestamp (read-only)
 */
class Company extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Company';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'company';

    /**
     * API endpoint path appended to base URL.
     *
     * Note: Unlike most resources, this is singular 'company' not 'companies'
     * because it's a singleton resource.
     *
     * @var string
     */
    public const API_PATH = 'company';

    /**
     * Properties required when creating a new company.
     *
     * Empty because Company cannot be created via API - it's a singleton.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = [];

    /**
     * Properties that cannot be modified via API.
     *
     * Most company properties are read-only, especially account limits,
     * image URLs, and payment gateway configurations.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'image', // Manually process with the ->upload method
      'image_thumb_large',
      'image_thumb_medium',
      'image_thumb_small',
      'max_users',
      'current_users',
      'max_projects',
      'current_projects',
      'account_type',
      'max_invoices',
      'current_invoices',
        // Undocumented Keys automatically treated as readonly
      'company_size',
      'due_interval',
      'estimate_format',
      'footer_unit_measure',
      'hide_tax_field',
      'invoice_format',
      'invoice_page_footer',
      'invoice_page_footer_height',
      'invoice_page_margin_width',
      'language',
      'margin_unit_measure',
      'op_authorize_login',
      'op_payflowpro_partner',
      'op_payflowpro_user',
      'op_payflowpro_vendor',
      'op_paypal_email',
      'op_stripe_publishable_key',
      'op_stripe_secret_key',
      'pdf_format_size',
      'workday_end',
      'new_invoice_email_subj_tpl',
      'new_invoice_email_body_tpl',
      'new_estimate_email_subj_tpl',
      'new_estimate_email_body_tpl',
      'new_paymentreminder_email_subj_tpl',
      'new_paymentreminder_email_body_tpl',
      'invoice_bill_to_fields',
      'default_invoice_footer',
      'default_estimate_footer',
      'custom_smtp_auth_type',
      'custom_smtp_port',
      'op_authorize_accepted_cc_amex',
      'op_authorize_accepted_cc_diners',
      'op_authorize_accepted_cc_discover',
      'op_authorize_accepted_cc_jcb',
      'op_authorize_accepted_cc_mastercard',
      'op_authorize_accepted_cc_visa',
      'show_delivery_date',
      'custom_domain',
      'active',
      'trial_ends_on',
      'default_invoice_template',
      'default_estimate_template',
      'max_estimates',
      'max_recurring_profiles',
      'max_expenses',
      'current_estimates',
      'current_recurring_profiles',
      'current_expenses'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Empty for Company - cannot be created via API.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Empty for Company - no related entities can be included.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [];

    /**
     * Property type definitions for validation and hydration.
     *
     * Company has extensive configuration properties covering account
     * settings, localization, invoicing, and payment gateways.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                                  => 'integer',
      'created_on'                          => 'datetime',
      'updated_on'                          => 'datetime',
      'name'                                => 'text',
      'address'                             => 'text',
      'phone'                               => 'text',
      'email'                               => 'email',
      'url'                                 => 'url',
      'fiscal_information'                  => 'text',
      'country'                             => 'text',
      'image'                               => 'url',
      'image_thumb_large'                   => 'url',
      'image_thumb_medium'                  => 'url',
      'image_thumb_small'                   => 'url',
      'timezone'                            => 'text',
      'default_currency'                    => 'text',
      'default_price_per_hour'              => 'text',
      'apply_tax_to_expenses'               => 'text',
      'tax_on_tax'                          => 'text',
      'currency_position'                   => 'enum:left|right',
      'next_invoice_number'                 => 'text',
      'next_estimate_number'                => 'text',
      'online_payments'                     => 'text',
      'date_format'                         => 'enum:Y-m-d|d/m/Y|m/d/Y|d.m.Y',
      'time_format'                         => 'enum:H:i|h:i a',
      'decimal_sep'                         => 'text',
      'thousands_sep'                       => 'text',
      'week_start'                          => 'integer',
      'workday_start'                       => 'text',
      'working_days'                        => 'array',
      'account_type'                        => 'enum:free|commercial',
      'max_users'                           => 'integer',
      'current_users'                       => 'integer',
      'max_projects'                        => 'integer',
      'current_projects'                    => 'integer',
      'max_invoices'                        => 'integer',
      'current_invoices'                    => 'integer',
        // Undocumented Props
      'company_size'                        => 'text',
      'due_interval'                        => 'text',
      'estimate_format'                     => 'text',
      'footer_unit_measure'                 => 'text',
      'hide_tax_field'                      => 'text',
      'invoice_format'                      => 'text',
      'invoice_page_footer'                 => 'text',
      'invoice_page_footer_height'          => 'text',
      'invoice_page_margin_width'           => 'text',
      'language'                            => 'text',
      'margin_unit_measure'                 => 'text',
      'op_authorize_login'                  => 'text',
      'op_payflowpro_partner'               => 'text',
      'op_payflowpro_user'                  => 'text',
      'op_payflowpro_vendor'                => 'text',
      'op_paypal_email'                     => 'text',
      'op_stripe_publishable_key'           => 'text',
      'op_stripe_secret_key'                => 'text',
      'pdf_format_size'                     => 'text',
      'workday_end'                         => 'text',
      'new_invoice_email_subj_tpl'          => 'text',
      'new_invoice_email_body_tpl'          => 'text',
      'new_estimate_email_subj_tpl'         => 'text',
      'new_estimate_email_body_tpl'         => 'text',
      'new_paymentreminder_email_subj_tpl'  => 'text',
      'new_paymentreminder_email_body_tpl'  => 'text',
      'invoice_bill_to_fields'              => 'text',
      'default_invoice_footer'              => 'text',
      'default_estimate_footer'             => 'text',
      'custom_smtp_auth_type'               => 'text',
      'custom_smtp_port'                    => 'text',
      'op_authorize_accepted_cc_amex'       => 'boolean',
      'op_authorize_accepted_cc_diners'     => 'boolean',
      'op_authorize_accepted_cc_discover'   => 'boolean',
      'op_authorize_accepted_cc_jcb'        => 'boolean',
      'op_authorize_accepted_cc_mastercard' => 'boolean',
      'op_authorize_accepted_cc_visa'       => 'boolean',
      'show_delivery_date'                  => 'boolean',
      'custom_domain'                       => 'text',
      'active'                              => 'boolean',
      'trial_ends_on'                       => 'datetime',
      'default_invoice_template'            => 'integer',
      'default_estimate_template'           => 'integer',
      'max_estimates'                       => 'interger',
      'max_recurring_profiles'              => 'integer',
      'max_expenses'                        => 'integer',
      'current_estimates'                   => 'integer',
      'current_recurring_profiles'          => 'integer',
      'current_expenses'                    => 'integer',
    ];

//["company_size"]=>
//string(5) "small"
//["due_interval"]=>
//string(2) "15"
//["estimate_format"]=>
//string(23) "#EST-[yyyy][mm][dd]-[i]"
//["footer_unit_measure"]=>
//string(2) "cm"
//["hide_tax_field"]=>
//string(1) "1"
//["invoice_format"]=>
//string(23) "#INV-[yyyy][mm][dd]-[i]"
//["invoice_page_footer"]=>
//string(0) ""
//["invoice_page_footer_height"]=>
//string(1) "1"
//["invoice_page_margin_width"]=>
//string(3) "1.8"
//["language"]=>
//string(2) "en"
//["margin_unit_measure"]=>
//string(2) "cm"
//["op_authorize_login"]=>
//string(0) ""
//["op_payflowpro_partner"]=>
//string(0) ""
//["op_payflowpro_user"]=>
//string(0) ""
//["op_payflowpro_vendor"]=>
//string(0) ""
//["op_paypal_email"]=>
//string(0) ""
//["op_stripe_publishable_key"]=>
//string(0) ""
//["op_stripe_secret_key"]=>
//string(0) ""
//["pdf_format_size"]=>
//string(6) "Letter"
//["workday_end"]=>
//string(5) "17:00"
//["new_invoice_email_subj_tpl"]=>
//string(50) "New invoice from [company_name] ([invoice_number])"
//["new_invoice_email_body_tpl"]=>
//string(132) "To access your invoice from [company_name], go to:<div><a href="[invoice_link]">[invoice_number]</a></div><div>[invoice_notes]</div>"
//["new_estimate_email_subj_tpl"]=>
//string(52) "New estimate from [company_name] ([estimate_number])"
//["new_estimate_email_body_tpl"]=>
//string(136) "To access your estimate from [company_name], go to:<div><a href="[estimate_link]">[estimate_number]</a></div><div>[estimate_notes]</div>"
//["new_paymentreminder_email_subj_tpl"]=>
//string(94) "Payment notification from [company_name]. Your payment for invoice [invoice_number] is overdue"
//["new_paymentreminder_email_body_tpl"]=>
//string(158) "Your invoice is now [days] days overdue. <div>To access your invoice from [company_name], go to:</div><div><a href="[invoice_link]">[invoice_number]</a></div>"
//["invoice_bill_to_fields"]=>
//string(86) "name,address,city,state,postal_code,country,phone,fax,email,website,fiscal_information"
//["default_invoice_footer"]=>
//string(28) "Thank you for your business."
//["default_estimate_footer"]=>
//string(28) "Thank you for your business."
//["custom_smtp_auth_type"]=>
//string(5) "login"
//["custom_smtp_port"]=>
//string(2) "25"
//["op_authorize_accepted_cc_amex"]=>
//bool(true)
//["op_authorize_accepted_cc_diners"]=>
//bool(true)
//["op_authorize_accepted_cc_discover"]=>
//bool(true)
//["op_authorize_accepted_cc_jcb"]=>
//bool(true)
//["op_authorize_accepted_cc_mastercard"]=>
//bool(true)
//["op_authorize_accepted_cc_visa"]=>
//bool(true)
//["show_delivery_date"]=>
//bool(true)
//["custom_domain"]=>
//NULL
//["active"]=>
//bool(true)
//["trial_ends_on"]=>
//string(20) "2019-12-20T20:41:01Z"
//["default_invoice_template"]=>
//int(226018)
//["default_estimate_template"]=>
//int(211406)
//["max_estimates"]=>
//NULL
//["max_recurring_profiles"]=>
//NULL
//["max_expenses"]=>
//NULL
//["current_estimates"]=>
//int(0)
//["current_recurring_profiles"]=>
//int(0)
//["current_expenses"]=>
//int(0)

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Empty for Company - list() is not supported.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * List operation is not supported for Company (singleton resource).
     *
     * Company is a singleton resource - only one exists per Paymo account.
     * Attempting to call list() will throw an exception.
     *
     * @param null $paymo The Paymo connection (ignored)
     *
     * @throws Exception Always throws - operation not supported
     * @return void
     */
    public static function list($paymo = null) : void
    {
        throw new RuntimeException("Company is a single resource and does not have a collection list");
    }

    /**
     * Create operation is not supported for Company (singleton resource).
     *
     * Company is automatically created when a Paymo account is created.
     * It cannot be created via the API.
     *
     * @param array $options Create options (ignored)
     *
     * @throws Exception Always throws - operation not supported
     * @return void
     */
    public function create($options = []) : void
    {
        throw new RuntimeException("Company is a single resource and cannot be created via the API");
    }

    /**
     * Delete operation is not supported for Company (singleton resource).
     *
     * Company cannot be deleted - it represents the Paymo account itself.
     * Only Paymo support can delete an account.
     *
     * @throws Exception Always throws - operation not supported
     * @return void
     */
    public function delete() : void
    {
        throw new RuntimeException("Company cannot be deleted through the API");
    }

}