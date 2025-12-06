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
 * ENTITY COLLECTION - DEFAULT COLLECTION IMPLEMENTATION
 * ======================================================================================
 *
 * This class serves as the default/generic collection class for Paymo entity types that
 * don't require special validation or custom behavior. It directly extends AbstractCollection
 * without adding any additional functionality, providing a clean implementation for
 * entities with no special collection requirements.
 *
 * INHERITANCE CHAIN:
 * ------------------
 * AbstractEntity → AbstractCollection → EntityCollection → Specialized Collections
 *
 * EntityCollection acts as a middle layer in the collection hierarchy:
 * - It inherits all collection behavior from AbstractCollection
 * - It serves as the parent class for specialized collections (BookingCollection, etc.)
 * - It can be used directly for entities without special requirements
 *
 * USAGE PATTERNS:
 * ---------------
 * Most entity types are configured in EntityMap to use EntityCollection directly.
 * Specialized collections extend this class to add validation requirements.
 *
 * DIRECT USAGE:
 * -------------
 * ```php
 * // Most entities use EntityCollection via EntityMap
 * // The collection class is automatically resolved
 * $projects = Project::list($connection, ['active' => true]);
 *
 * // Internally, EntityMap returns EntityCollection for most types
 * $collectionClass = EntityMap::collection('projects');
 * // Returns: Jcolombo\PaymoApiPhp\Entity\Collection\EntityCollection
 * ```
 *
 * EXTENSION PATTERN:
 * ------------------
 * ```php
 * // Specialized collections extend EntityCollection
 * class BookingCollection extends EntityCollection
 * {
 *     protected function validateFetch($fields = [], $where = [])
 *     {
 *         // Add custom validation
 *         return parent::validateFetch($fields, $where);
 *     }
 * }
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Collection
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractCollection Parent class with full collection functionality
 * @see        EntityMap Maps entity keys to collection classes
 * @see        BookingCollection Example of specialized collection
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Jcolombo\PaymoApiPhp\Entity\AbstractCollection;

/**
 * Default collection implementation for Paymo entity types.
 *
 * EntityCollection is the standard collection class used by most Paymo entities.
 * It provides all collection functionality inherited from AbstractCollection without
 * adding additional validation or constraints. This makes it suitable for entity
 * types that don't have special API requirements for list fetches.
 *
 * Specialized collections (BookingCollection, TimeEntryCollection, etc.) extend
 * this class to add Paymo API-specific validation requirements, such as mandatory
 * filter conditions.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class EntityCollection extends AbstractCollection
{

}