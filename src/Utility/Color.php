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
 * COLOR UTILITY - PAYMO COLOR PALETTE HELPER
 * ======================================================================================
 *
 * This utility class provides access to Paymo's default color palette. Many Paymo
 * resources support color properties (projects, tasklists, etc.), and this class
 * helps select colors that match Paymo's built-in color picker.
 *
 * AVAILABLE COLOR SETS:
 * ---------------------
 * - red: Pink to deep red tones
 * - redOrange: Coral and salmon tones
 * - orange: Orange to yellow tones
 * - green: Green tones from dark to light
 * - greenBlue: Teal and cyan tones
 * - blue: Blue tones from dark to light
 * - purple: Purple and lavender tones
 * - grayscale: Gray tones from dark to light
 *
 * Each color set contains 5 shades from darkest to lightest.
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * // Get a completely random color
 * $color = Color::random();
 *
 * // Get a random blue
 * $color = Color::byName('blue');
 *
 * // Get the darkest blue (index 0)
 * $color = Color::byName('blue', 0);
 *
 * // Get the lightest green (index 4)
 * $color = Color::byName('green', 4);
 *
 * // Use in project creation
 * $project = new Project();
 * $project->name = 'New Project';
 * $project->color = Color::random();
 * $project->create();
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Utility
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 */

namespace Jcolombo\PaymoApiPhp\Utility;


use Exception;

/**
 * Helper class for working with Paymo's color palette.
 *
 * Provides easy access to Paymo's default color values, which match the colors
 * available in Paymo's interface color picker. Use this to ensure visual
 * consistency between programmatically created resources and those created
 * through the Paymo UI.
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class Color
{
    /**
     * Paymo's default color palette organized by color family.
     *
     * Contains the hex color values from Paymo's interface color picker
     * (as of March 2020). Each color family has 5 shades arranged from
     * darkest (index 0) to lightest (index 4).
     *
     * COLOR VALUES:
     * -------------
     * All values are 6-character hex strings WITHOUT the leading '#'.
     * Add '#' prefix if needed for CSS or other contexts.
     *
     * STRUCTURE:
     * ----------
     * ```php
     * [
     *     'colorFamily' => [
     *         'DARKEST',    // index 0
     *         'DARK',       // index 1
     *         'MEDIUM',     // index 2
     *         'LIGHT',      // index 3
     *         'LIGHTEST',   // index 4
     *     ],
     * ]
     * ```
     *
     * @var array<string, array<int, string>> Color families with their shade arrays
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
     * Get a random color from any color family.
     *
     * Selects a random color family, then returns a random shade from that family.
     * Useful when you want any valid Paymo color without preference.
     *
     * USAGE:
     * ------
     * ```php
     * // Get any random color
     * $color = Color::random();
     * echo $color;  // e.g., "3E9FE7"
     *
     * // Use in resource
     * $tasklist->color = Color::random();
     * ```
     *
     * @throws Exception
     * @return string 6-character hex color string (without '#' prefix)
     *
     * @see byName() For selecting from a specific color family
     */
    public static function random() : string
    {
        $sets = array_keys(self::PAYMO_DEFAULT_COLORS);

        return self::byName($sets[random_int(0, (count($sets) - 1))]);
    }

    /**
     * Get a color from a specific color family.
     *
     * Retrieves a color from the named color family. If no index is provided,
     * returns a random shade from that family. If an index is provided,
     * returns the specific shade at that position (0=darkest, 4=lightest).
     *
     * AVAILABLE FAMILIES:
     * -------------------
     * - 'red': Pink to deep red tones
     * - 'redOrange': Coral and salmon tones
     * - 'orange': Orange to yellow tones
     * - 'green': Green tones
     * - 'greenBlue': Teal and cyan tones
     * - 'blue': Blue tones
     * - 'purple': Purple and lavender tones
     * - 'grayscale': Gray tones
     *
     * USAGE:
     * ------
     * ```php
     * // Random blue shade
     * $color = Color::byName('blue');
     *
     * // Specific shade (0 = darkest, 4 = lightest)
     * $darkBlue = Color::byName('blue', 0);     // "238CD7"
     * $lightBlue = Color::byName('blue', 4);    // "5BDBF6"
     *
     * // Random from each family
     * $randomGreen = Color::byName('green');
     * $randomPurple = Color::byName('purple');
     * ```
     *
     * @param string   $color The color family name (one of the PAYMO_DEFAULT_COLORS keys)
     * @param int|null $index Optional shade index (0-4). Null for random shade.
     *
     * @throws Exception
     * @return string 6-character hex color string (without '#' prefix)
     *
     * @see random() For completely random color selection
     * @see PAYMO_DEFAULT_COLORS For available color families and their values
     */
    public static function byName(string $color, int $index = null) : string
    {
        $colorSet = self::PAYMO_DEFAULT_COLORS[$color];
        $pick = $index ?? random_int(0, (count($colorSet) - 1));

        return $colorSet[$pick];
    }
}