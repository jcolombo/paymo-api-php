<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 4:37 PM
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
 * INVOICE TEMPLATE RESOURCE - PAYMO INVOICE DESIGN TEMPLATES
 * ======================================================================================
 *
 * This resource class represents a Paymo invoice template. Invoice templates
 * define the visual layout and styling for client invoices. Templates can be
 * customized with HTML and CSS for branded invoice presentation.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Custom HTML/CSS design
 * - Default template designation
 * - Invoice usage tracking
 * - Brand customization
 *
 * TEMPLATE STRUCTURE:
 * -------------------
 * - name: Internal reference name
 * - title: Display title on invoices
 * - html: HTML layout template
 * - css: Custom styling
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique template identifier (read-only)
 * - name: Template name (required)
 * - title: Display title
 * - html: HTML template content
 * - css: CSS styling
 * - is_default: Whether this is the default template
 * - invoices_count: Number of invoices using this template (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceTemplate;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new invoice template
 * $template = new InvoiceTemplate();
 * $template->name = 'Corporate Invoice';
 * $template->title = 'INVOICE';
 * $template->html = '<div class="invoice-header">{{company_name}}</div>...';
 * $template->css = '.invoice-header { font-size: 28px; font-weight: bold; }';
 * $template->create($connection);
 *
 * // Fetch template with associated invoices
 * $template = InvoiceTemplate::fetch($connection, 12345, [
 *     'include' => ['invoices']
 * ]);
 *
 * // List all templates
 * $templates = InvoiceTemplate::list($connection);
 *
 * // Set as default template
 * $template->is_default = true;
 * $template->update($connection);
 *
 * // Check usage count
 * echo "Used by " . $template->invoices_count . " invoices";
 * ```
 *
 * GALLERY TEMPLATES:
 * ------------------
 * Paymo provides pre-built templates via InvoiceTemplateGallery. These
 * are read-only and can be copied to create custom templates.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Invoice Invoices using templates
 * @see        InvoiceTemplateGallery Pre-built templates
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo InvoiceTemplate resource for invoice design.
 *
 * Invoice templates define the visual layout for client invoices.
 * This class provides full CRUD operations with HTML/CSS customization.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id             Unique template ID (read-only)
 * @property string $name           Template name (required)
 * @property string $title          Display title
 * @property string $html           HTML template content
 * @property string $css            CSS styling
 * @property bool   $is_default     Whether this is the default template
 * @property int    $invoices_count Number of invoices using template (read-only)
 * @property string $created_on     Creation timestamp (read-only)
 * @property string $updated_on     Last update timestamp (read-only)
 */
class InvoiceTemplate extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Invoice Template';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'invoicetemplate';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'invoicetemplates';

    /**
     * Properties required when creating a new invoice template.
     *
     * Only 'name' is required - other properties have defaults.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * Properties that cannot be modified via API.
     *
     * invoices_count is computed by the server.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'invoices_count'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for invoice templates.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - invoices: Invoices using this template (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['invoices' => true];

    /**
     * Property type definitions for validation and hydration.
     *
     * HTML and CSS are stored as text - validation is left to the user.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'             => 'integer',
      'created_on'     => 'datetime',
      'updated_on'     => 'datetime',
      'name'           => 'text',
      'title'          => 'text',
      'html'           => 'text',
      'css'            => 'text',
      'is_default'     => 'boolean',
        // Undocumented Props
      'invoices_count' => 'integer'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for invoice templates.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}