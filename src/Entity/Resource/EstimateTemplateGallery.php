<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/17/20, 8:48 PM
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
 * Class EstimateTemplateGallery
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class EstimateTemplateGallery extends AbstractResource
{

    /**
     * The user friendly name for error displays, messages, alerts, and logging
     */
    public const LABEL = 'Estimate Template Gallery';

    /**
     * The entity key for associations and references between the package code classes
     */
    public const API_ENTITY = 'estimatetemplatesgallery';

    /**
     * The path that is attached to the API base URL for the api call
     */
    public const API_PATH = 'estimatetemplatesgallery';

    /**
     * The minimum properties that must be set in order to create a new entry via the API
     * To make an OR limit: 'propA|propB' = ONLY 1 of these. 'propA||propB' = AT LEAST 1 or more of these.
     */
    public const REQUIRED_CREATE = [];

    /**
     * The object properties that can only be read and never set, updated, or added to the creation
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'name', 'title', 'html', 'css', 'image'];

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
        'title' => 'text',
        'html' => 'text',
        'css' => 'text',
        'image' => 'url'
        // Undocumented Props        
    ];

    /**
     * Allowable operators for list() calls on specific properties
     * Use [prop] = ['=','!='] to allow these only. Use [!prop] = ['like'] to NOT allow these types
     */
    public const WHERE_OPERATIONS = [];

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
        throw new Exception("Estimate Template Gallery resources cannot be created");
    }

    /**
     * Cannot be called on this resource
     *
     * @throws Exception
     * @return void
     */
    public function delete()
    {
        throw new Exception("Estimate Template Gallery resources cannot be deleted");
    }

    /**
     * Cannot be called on this resource
     *
     * @param array $options {@see AbstractResource::create()}
     *
     * @throws Exception
     * @return void
     */
    public function update($options = [])
    {
        throw new Exception("Estimate Template Gallery resources cannot be updated");
    }

    /**
     * @param $objClass
     *
     * @return string
     */
    protected function getResponseKey($objClass)
    {
        return ':estimatetemplates';
    }
}