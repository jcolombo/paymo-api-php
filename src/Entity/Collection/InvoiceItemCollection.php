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
 * INVOICE ITEM COLLECTION - LINE ITEMS FOR INVOICES
 * ======================================================================================
 *
 * This specialized collection class handles Paymo invoice item entities. Invoice
 * items are the individual line items that make up an invoice document, including
 * descriptions, quantities, rates, taxes, and totals.
 *
 * API FILTER REQUIREMENTS:
 * ------------------------
 * The Paymo API requires invoice items to be fetched in the context of a parent
 * invoice. You cannot fetch all invoice items across all invoices - you must
 * specify which invoice's items you want.
 *
 * REQUIRED FILTER:
 * ----------------
 * - invoice_id: MUST be specified (exactly one filter is required)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceItem;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Get all items for a specific invoice
 * $items = InvoiceItem::list($connection, [
 *     'where' => [
 *         RequestCondition::where('invoice_id', 12345),
 *     ]
 * ]);
 *
 * // Calculate invoice subtotal
 * $subtotal = 0;
 * foreach ($items as $item) {
 *     $subtotal += $item->amount;
 * }
 * echo "Subtotal: $" . number_format($subtotal, 2) . "\n";
 *
 * // Iterate through line items
 * foreach ($items as $item) {
 *     echo $item->description . ": ";
 *     echo $item->quantity . " x $" . $item->price . " = ";
 *     echo "$" . $item->amount . "\n";
 * }
 *
 * // This will throw an Exception - invoice_id is required!
 * $items = InvoiceItem::list($connection); // FAILS
 * ```
 *
 * ALTERNATIVE ACCESS:
 * -------------------
 * Invoice items can also be accessed via the parent Invoice resource using
 * the 'include' option:
 *
 * ```php
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Invoice;
 *
 * // Fetch invoice with its items included
 * $invoice = Invoice::fetch($connection, 12345, [
 *     'include' => ['invoiceitems']
 * ]);
 *
 * // Access items through the invoice
 * $items = $invoice->invoiceitems;
 * ```
 *
 * ERROR HANDLING:
 * ---------------
 * If invoice_id filter is not provided, an Exception is thrown before the
 * API request is made.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Collection
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        EntityCollection Parent collection class
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\InvoiceItem The invoice item resource
 * @see        \Jcolombo\PaymoApiPhp\Entity\Resource\Invoice Parent invoice resource
 * @see        RequestCondition For building filter conditions
 */

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;
use RuntimeException;

/**
 * Specialized collection for Paymo invoice item entities.
 *
 * Enforces Paymo API requirements for invoice item list fetches, which require
 * the invoice_id filter to be specified. Invoice items can only be fetched
 * within the context of a specific parent invoice.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Collection
 */
class InvoiceItemCollection extends EntityCollection
{
    /**
     * Validate that the invoice_id filter is present before fetching.
     *
     * The Paymo API requires invoice item list requests to specify an
     * invoice_id. This ensures items are always fetched within the context
     * of their parent invoice document.
     *
     * VALIDATION LOGIC:
     * -----------------
     * 1. Scans all WHERE conditions for 'invoice_id' property
     * 2. If found, allows the request to proceed
     * 3. If not found, throws Exception with clear error message
     *
     * @param array $fields Optional fields parameter (passed to parent)
     * @param array $where  Array of RequestCondition objects to validate
     *
     * @throws Exception If invoice_id filter is not found in WHERE conditions.
     *
     * @return bool Returns true if validation passes (from parent)
     *
     * @see AbstractCollection::validateFetch() Parent validation method
     */
    protected function validateFetch($fields = [], $where = []) : bool
    {
        $foundOne = false;
        foreach ($where as $w) {
            if ($w->prop === 'invoice_id') {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new RuntimeException("Invoice item collections require a where condition filter set on invoice_id");
        }

        return parent::validateFetch($fields, $where);
    }
}