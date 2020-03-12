<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/12/20, 3:54 PM
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

namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Exception;

class TaskAssignmentCollection extends EntityCollection
{

    /**
     * Wrap validation for fetches to insure user_id and/or task_id WHERE condition has been met
     * {@inheritDoc}
     */
    protected function validateFetch($fields=[], $where=[]) {
        // @todo Find out what these do (undocumented)... 'booking_date', 'has_bookings', 'task_dates', 'task_complete'
        $needOne = ['task_id', 'user_id', 'booking_date', 'has_bookings', 'task_dates', 'task_complete'];
        $foundOne = false;
        foreach($where as $w) {
            if (in_array($w->prop, $needOne)) {
                $foundOne = true;
                break;
            }
        }
        if (!$foundOne) {
            throw new Exception("Task Assigment collections require at least one of the following be set as a filter : ".implode(', ', $needOne));
        }

        return parent::validateFetch($fields, $where);
    }

}