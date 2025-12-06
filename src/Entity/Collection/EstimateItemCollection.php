<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 10:48 PM
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
 * ESTIMATE ITEM COLLECTION - LINE ITEMS FOR ESTIMATES
 * ======================================================================================
 *
 * This specialized collection class handles Paymo estimate item entities. Estimate
 * items are the individual line items that make up an estimate document, including
 * descriptions, quantities, rates, and totals.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires estimate items to be fetched in the context of a parent
 * estimate. You cannot fetch all estimate items across all estimates - you must
 * specify which estimate's items you want.
 *
 * REQUIRED FILTER:
 * ----------------
 * - estimate_id: MUST be specified (exactly one filter is required)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\EstimateItem;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Get all items for a specific estimate
 * $items = EstimateItem::list($connection, [
 *     'where' => [
 *         RequestCondition::where('estimate_id', 12345),
 *     ]
 * ]);
 *
 * // Iterate through the line items
 * foreach ($items as $item) {
 *     echo $item->description . ": $" . $item->amount . "\n";
 * }
 *
 * // This will throw an Exception - estimate_id is required!
 * $items = EstimateItem::list($connection); // FAILS
 * ```
 *
 * ALTERNATIVE ACCESS:
 * -------------------
 * Estimate items can also be accessed via the parent Estimate resource using
 * the 'include' option:
 *
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Estimate;
 *
 * // Fetch estimate with its items included
 * $estimate = Estimate::fetch($connection, 12345, [
 *     'include' => ['estimateitems']
 * ]);
 *
 * // Access items through the estimate
 * $items = $estimate->estimateitems;
 * ```
 *
 * ERROR HANDLING:
 * ---------------
 * If estimate_id filter is not provided, an Exception is thrown before the
 * API request is made.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Collection
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        EntityCollection Parent collection class
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\EstimateItem The estimate item resource
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\Estimate Parent estimate resource
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo estimate item entities.
 *
 * Enforces Paymo API requirements for estimate item list fetches, which require
 * the estimate_id filter to be specified. Estimate items can only be fetched
 * within the context of a specific parent estimate.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class EstimateItemCollection extends EntityCollection
{
    /**
     * Validate that the estimate_id filter is present before fetching.
     *
     * The Paymo API requires estimate item list requests to specify an
     * estimate_id. This ensures items are always fetched within the context
     * of their parent estimate document.
     *
     * VALIDATION LOGIC:
     * -----------------
     * 1. Scans all WHERE conditions for 'estimate_id' property
     * 2. If found, allows the request to proceed
     * 3. If not found, throws Exception with clear error message
     *
     * @param array $fields Optional fields parameter (passed to parent)
     * @param array $where  Array of RequestCondition objects to validate
     *
     * @throws Exception If estimate_id filter is not found in WHERE conditions.
     *
     * @return bool Returns true if validation passes (from parent)
     *
     * @see AbstractCollection::validateFetch() Parent validation method
     */
    protected function validateFetch($fields = [], $where = []) : bool
    {
        $foundOne = false;
        foreach ($where as $w) {
            if ($w->prop === 'estimate_id') {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new RuntimeException("Estimate item collections require a where condition filter set on estimate_id");
        }

        return parent::validateFetch($fields, $where);
    }
}