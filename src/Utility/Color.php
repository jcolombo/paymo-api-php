<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/11/20, 6:52 PM
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

namespace Jcolombo\PaymoApiPhp\Utility;

class Color
{

    /**
     * An array containing the default colors from the paymo interface popup picker (as of 2020-03-11)
     */
    public const PAYMO_DEFAULT_COLORS = [
        'red' => [
            "E93A55", "FD4C67", "E36DAB", "F884BE", "FFA9C5",
        ],
        'redOrange' => [
            "F94E45", "FF6657", "FF8657", "FFA274", "FFBAA2",
        ],
        'orange' => [
            "FF8549", "FFA64F", "FFB855", "FFDB60", "FFE69B",
        ],
        'green' => [
            "3E993C", "55AE4E", "84C15F", "98D473", "BBE584",
        ],
        'greenBlue' => [
            "1E8A78", "00A287", "00BD9D", "00D1AF", "2DE3AD",
        ],
        'blue' => [
            "238CD7", "3E9FE7", "00B2D7", "11CAEA", "5BDBF6",
        ],
        'purple' => [
            "6D65A8", "7B71C6", "967CD7", "AC94E7", "C6ADF5",
        ],
        'grayscale' => [
            "414A53", "636D77", "A8B2BC", "CBD1D8", "E5E9ED",
        ],
    ];

    /**
     * Get a random color from the Paymo presets
     *
     * @return string A random hex color from the preset colors ready to use on any color properties of resources
     */
    public static function random()
    {
        $sets = array_keys(self::PAYMO_DEFAULT_COLORS);

        return self::byName($sets[rand(0, (count($sets) - 1))]);
    }

    /**
     * Get a specific color (or random color from a collection/set)
     *
     * @param string $color A valid string key for the preset color groups
     * @param null   $index An optional specific position of that color to get (0 to 4, initially)
     *
     * @return string Get the hex color ready to use in any paymo color set fields
     */
    public static function byName($color, $index = null)
    {
        $colorSet = self::PAYMO_DEFAULT_COLORS[$color];
        $pick = $index ?? rand(0, (count($colorSet) - 1));

        return $colorSet[$pick];
    }

}