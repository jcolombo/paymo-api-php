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
 * INVOICE TEMPLATE GALLERY RESOURCE - PAYMO PRE-BUILT INVOICE TEMPLATES
 * ======================================================================================
 *
 * This resource class represents a Paymo invoice template gallery item. The gallery
 * contains pre-built, read-only invoice templates provided by Paymo. These templates
 * can be viewed and their HTML/CSS copied to create custom InvoiceTemplate resources.
 *
 * RESTRICTED OPERATIONS:
 * ----------------------
 * This is a READ-ONLY resource:
 * - fetch(): Supported - retrieve gallery template details
 * - list(): Supported - browse available gallery templates
 * - create(): NOT SUPPORTED - gallery templates are Paymo-provided
 * - update(): NOT SUPPORTED - gallery templates cannot be modified
 * - delete(): NOT SUPPORTED - gallery templates cannot be removed
 *
 * KEY FEATURES:
 * -------------
 * - Read-only access to pre-built templates
 * - Professional design templates
 * - HTML and CSS source for copying
 * - Preview images
 * - Starting points for custom templates
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * All Properties (read-only):
 * - id: Unique gallery item identifier
 * - name: Template name
 * - title: Display title
 * - html: HTML template content
 * - css: CSS styling
 * - image: Preview image URL
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceTemplateGallery;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceTemplate;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // List available gallery templates
 * $gallery = InvoiceTemplateGallery::list($connection);
 *
 * // Browse gallery templates
 * foreach ($gallery as $item) {
 *     echo $item->name . " - " . $item->title . "\n";
 *     echo "Preview: " . $item->image . "\n";
 * }
 *
 * // Fetch a specific gallery template
 * $galleryItem = InvoiceTemplateGallery::fetch($connection, 12345);
 *
 * // Copy gallery template to create custom template
 * $customTemplate = new InvoiceTemplate();
 * $customTemplate->name = 'My Custom Invoice';
 * $customTemplate->title = $galleryItem->title;
 * $customTemplate->html = $galleryItem->html;
 * $customTemplate->css = $galleryItem->css;
 * $customTemplate->create($connection);
 *
 * // WILL THROW EXCEPTIONS:
 * // $galleryItem->create($connection);  // Cannot create
 * // $galleryItem->update($connection);  // Cannot update
 * // $galleryItem->delete();             // Cannot delete
 * ```
 *
 * RESPONSE KEY MAPPING:
 * ---------------------
 * This resource uses ':invoicetemplates' as a special response key
 * for processing API results.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        InvoiceTemplate Custom invoice templates
 * @see        Invoice Invoices using templates
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use RuntimeException;

/**
 * Paymo InvoiceTemplateGallery resource for pre-built invoice templates.
 *
 * Gallery items are read-only Paymo-provided templates. Use them as
 * starting points for creating custom InvoiceTemplate resources.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id         Unique gallery item ID (read-only)
 * @property string $name       Template name (read-only)
 * @property string $title      Display title (read-only)
 * @property string $html       HTML template content (read-only)
 * @property string $css        CSS styling (read-only)
 * @property string $image      Preview image URL (read-only)
 * @property string $created_on Creation timestamp (read-only)
 * @property string $updated_on Last update timestamp (read-only)
 */
class InvoiceTemplateGallery extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Invoice Template Gallery';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'invoicetemplatesgallery';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'invoicetemplatesgallery';

    /**
     * Properties required when creating a new resource.
     *
     * Empty because creation is not supported for gallery items.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = [];

    /**
     * Properties that cannot be modified via API.
     *
     * All properties are read-only - gallery templates are Paymo-provided.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'name', 'title', 'html', 'css', 'image'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Empty because creation is not supported.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Gallery items have no related entities.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [];

    /**
     * Property type definitions for validation and hydration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'         => 'integer',
      'created_on' => 'datetime',
      'updated_on' => 'datetime',
      'name'       => 'text',
      'title'      => 'text',
      'html'       => 'text',
      'css'        => 'text',
      'image'      => 'url'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for gallery items.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Create operation is not supported for InvoiceTemplateGallery.
     *
     * Gallery templates are provided by Paymo and cannot be created.
     * To create a custom template, use the InvoiceTemplate resource.
     *
     * @param array $options Create options (ignored)
     *
     * @throws Exception Always throws - gallery items cannot be created
     * @return void
     */
    public function create($options = []) : void
    {
        throw new RuntimeException("Invoice Template Gallery resources cannot be created");
    }

    /**
     * Delete operation is not supported for InvoiceTemplateGallery.
     *
     * Gallery templates are provided by Paymo and cannot be deleted.
     *
     * @throws Exception Always throws - gallery items cannot be deleted
     * @return void
     */
    public function delete() : void
    {
        throw new RuntimeException("Invoice Template Gallery resources cannot be deleted");
    }

    /**
     * Update operation is not supported for InvoiceTemplateGallery.
     *
     * Gallery templates are read-only and cannot be modified.
     * To customize a template, copy it to an InvoiceTemplate resource.
     *
     * @param array $options Update options (ignored)
     *
     * @throws Exception Always throws - gallery items cannot be updated
     * @return void
     */
    public function update($options = []) : void
    {
        throw new RuntimeException("Invoice Template Gallery resources cannot be updated");
    }

    /**
     * Get the response key for parsing API results.
     *
     * Overrides parent to use special ':invoicetemplates' key for
     * gallery response processing.
     *
     * @param string $objClass The class being processed
     *
     * @return string The response key to use
     */
    protected function getResponseKey($objClass) : string
    {
        return ':invoicetemplates';
    }
}