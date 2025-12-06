<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/17/20, 4:41 PM
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
 * ESTIMATE TEMPLATE RESOURCE - PAYMO QUOTE DESIGN TEMPLATES
 * ======================================================================================
 *
 * This resource class represents a Paymo estimate template. Estimate templates
 * define the visual layout and styling for client quotes/proposals. Templates
 * can be customized with HTML and CSS for branded estimate presentation.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Custom HTML/CSS design
 * - Default template designation
 * - Estimate usage tracking
 * - Brand customization
 *
 * TEMPLATE STRUCTURE:
 * -------------------
 * - name: Internal reference name
 * - title: Display title on estimates
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
 * - estimates_count: Number of estimates using this template (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\EstimateTemplate;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new estimate template
 * $template = new EstimateTemplate();
 * $template->name = 'Modern Quote';
 * $template->title = 'Project Quote';
 * $template->html = '<div class="quote-header">{{company_name}}</div>...';
 * $template->css = '.quote-header { font-size: 24px; color: #333; }';
 * $template->create($connection);
 *
 * // Fetch template with associated estimates
 * $template = EstimateTemplate::fetch($connection, 12345, [
 *     'include' => ['estimates']
 * ]);
 *
 * // List all templates
 * $templates = EstimateTemplate::list($connection);
 *
 * // Set as default template
 * $template->is_default = true;
 * $template->update($connection);
 *
 * // Check usage count
 * echo "Used by " . $template->estimates_count . " estimates";
 * ```
 *
 * GALLERY TEMPLATES:
 * ------------------
 * Paymo provides pre-built templates via EstimateTemplateGallery. These
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
 * @see        Estimate Estimates using templates
 * @see        EstimateTemplateGallery Pre-built templates
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo EstimateTemplate resource for quote design.
 *
 * Estimate templates define the visual layout for client quotes.
 * This class provides full CRUD operations with HTML/CSS customization.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id              Unique template ID (read-only)
 * @property string $name            Template name (required)
 * @property string $title           Display title
 * @property string $html            HTML template content
 * @property string $css             CSS styling
 * @property bool   $is_default      Whether this is the default template
 * @property int    $estimates_count Number of estimates using template (read-only)
 * @property string $created_on      Creation timestamp (read-only)
 * @property string $updated_on      Last update timestamp (read-only)
 */
class EstimateTemplate extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Estimate Template';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'estimatetemplate';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'estimatetemplates';

    /**
     * Properties required when creating a new estimate template.
     *
     * Only 'name' is required - other properties have defaults.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * Properties that cannot be modified via API.
     *
     * estimates_count is computed by the server.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'estimates_count'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for estimate templates.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - estimates: Estimates using this template (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['estimates' => true];

    /**
     * Property type definitions for validation and hydration.
     *
     * HTML and CSS are stored as text - validation is left to the user.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'              => 'integer',
      'created_on'      => 'datetime',
      'updated_on'      => 'datetime',
      'name'            => 'text',
      'title'           => 'text',
      'html'            => 'text',
      'css'             => 'text',
      'is_default'      => 'boolean',
        // Undocumented Props
      'estimates_count' => 'integer'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for estimate templates.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}