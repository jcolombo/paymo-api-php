<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/12/20, 11:07 AM
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

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Class Company
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class Company extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Company';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'company';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'company';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     */
    public const REQUIRED_CREATE = [];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = [
        'id', 'created_on', 'updated_on',
        'image', // Manually process with the ->upload method
        'image_thumb_large', 'image_thumb_medium', 'image_thumb_small',
        'max_users', 'current_users', 'max_projects', 'current_projects',
        'account_type', 'max_invoices', 'current_invoices',
        // Undocumented Keys automatically treated as readonly
        'company_size', 'due_interval', 'estimate_format', 'footer_unit_measure', 'hide_tax_field', 'invoice_format',
        'invoice_page_footer', 'invoice_page_footer_height', 'invoice_page_margin_width', 'language',
        'margin_unit_measure', 'op_authorize_login', 'op_payflowpro_partner', 'op_payflowpro_user',
        'op_payflowpro_vendor', 'op_paypal_email', 'op_stripe_publishable_key', 'op_stripe_secret_key',
        'pdf_format_size', 'workday_end', 'new_invoice_email_subj_tpl', 'new_invoice_email_body_tpl',
        'new_estimate_email_subj_tpl', 'new_estimate_email_body_tpl', 'new_paymentreminder_email_subj_tpl',
        'new_paymentreminder_email_body_tpl', 'invoice_bill_to_fields', 'default_invoice_footer',
        'default_estimate_footer', 'custom_smtp_auth_type', 'custom_smtp_port', 'op_authorize_accepted_cc_amex',
        'op_authorize_accepted_cc_diners', 'op_authorize_accepted_cc_discover', 'op_authorize_accepted_cc_jcb',
        'op_authorize_accepted_cc_mastercard', 'op_authorize_accepted_cc_visa', 'show_delivery_date', 'custom_domain',
        'active', 'trial_ends_on', 'default_invoice_template', 'default_estimate_template', 'max_estimates',
        'max_recurring_profiles', 'max_expenses', 'current_estimates', 'current_recurring_profiles',
        'current_expenses'
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
    public const INCLUDE_TYPES = [];

    /**
     * Valid property types returned from the API json object for this entity
     */
    public const PROP_TYPES = [
        'id' => 'integer',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'name' => 'text',
        'address' => 'text',
        'phone' => 'text',
        'email' => 'email',
        'url' => 'url',
        'fiscal_information' => 'text',
        'country' => 'text',
        'image' => 'url',
        'image_thumb_large' => 'url',
        'image_thumb_medium' => 'url',
        'image_thumb_small' => 'url',
        'timezone' => 'text',
        'default_currency' => 'text',
        'default_price_per_hour' => 'text',
        'apply_tax_to_expenses' => 'text',
        'tax_on_tax' => 'text',
        'currency_position' => 'enum:left|right',
        'next_invoice_number' => 'text',
        'next_estimate_number' => 'text',
        'online_payments' => 'text',
        'date_format' => 'enum:Y-m-d|d/m/Y|m/d/Y|d.m.Y',
        'time_format' => 'enum:H:i|h:i a',
        'decimal_sep' => 'text',
        'thousands_sep' => 'text',
        'week_start' => 'integer',
        'workday_start' => 'text',
        'working_days' => 'array',
        'account_type' => 'enum:free|commercial',
        'max_users' => 'integer',
        'current_users' => 'integer',
        'max_projects' => 'integer',
        'current_projects' => 'integer',
        'max_invoices' => 'integer',
        'current_invoices' => 'integer',
        // Undocumented Props
        'company_size' => 'text',
        'due_interval' => 'text',
        'estimate_format' => 'text',
        'footer_unit_measure' => 'text',
        'hide_tax_field' => 'text',
        'invoice_format' => 'text',
        'invoice_page_footer' => 'text',
        'invoice_page_footer_height' => 'text',
        'invoice_page_margin_width' => 'text',
        'language' => 'text',
        'margin_unit_measure' => 'text',
        'op_authorize_login' => 'text',
        'op_payflowpro_partner' => 'text',
        'op_payflowpro_user' => 'text',
        'op_payflowpro_vendor' => 'text',
        'op_paypal_email' => 'text',
        'op_stripe_publishable_key' => 'text',
        'op_stripe_secret_key' => 'text',
        'pdf_format_size' => 'text',
        'workday_end' => 'text',
        'new_invoice_email_subj_tpl' => 'text',
        'new_invoice_email_body_tpl' => 'text',
        'new_estimate_email_subj_tpl' => 'text',
        'new_estimate_email_body_tpl' => 'text',
        'new_paymentreminder_email_subj_tpl' => 'text',
        'new_paymentreminder_email_body_tpl' => 'text',
        'invoice_bill_to_fields' => 'text',
        'default_invoice_footer' => 'text',
        'default_estimate_footer' => 'text',
        'custom_smtp_auth_type' => 'text',
        'custom_smtp_port' => 'text',
        'op_authorize_accepted_cc_amex' => 'boolean',
        'op_authorize_accepted_cc_diners' => 'boolean',
        'op_authorize_accepted_cc_discover' => 'boolean',
        'op_authorize_accepted_cc_jcb' => 'boolean',
        'op_authorize_accepted_cc_mastercard' => 'boolean',
        'op_authorize_accepted_cc_visa' => 'boolean',
        'show_delivery_date' => 'boolean',
        'custom_domain' => 'text',
        'active' => 'boolean',
        'trial_ends_on' => 'datetime',
        'default_invoice_template' => 'integer',
        'default_estimate_template' => 'integer',
        'max_estimates' => 'interger',
        'max_recurring_profiles' => 'integer',
        'max_expenses' => 'integer',
        'current_estimates' => 'integer',
        'current_recurring_profiles' => 'integer',
        'current_expenses' => 'integer',
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
     * Allowable operators for list() calls on specific properties
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Cannot be called on this resource
     *
     * @param null $paymo {@see AbstractResource::list()}
     *
     * @throws Exception
     * @return void
     */
    public static function list($paymo = null)
    {
        throw new Exception("Company is a single resource and does not have a collection list");
    }

    /**
     * Cannot be called on this resource
     *
     * @param array $options {@see AbstractResource::create()}
     *
     * @throws Exception
     * @return void
     */
    public function create($options = [])
    {
        throw new Exception("Company is a single resource and cannot be created via the API");
    }

    /**
     * Cannot be called on this resource
     *
     * @throws Exception
     * @return void
     */
    public function delete()
    {
        throw new Exception("Company cannot be deleted through the API");
    }

}