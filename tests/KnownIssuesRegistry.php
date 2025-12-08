<?php
/**
 * Paymo API PHP SDK - Known Issues Registry
 *
 * Tracks expected API behaviors, known limitations, and handled edge cases.
 * This allows the test suite to distinguish between:
 * - NEW failures (unexpected - should be investigated)
 * - KNOWN issues (expected - already handled in SDK)
 * - RESOLVED issues (previously known, now working - can be removed)
 *
 * USAGE:
 * ------
 * ```php
 * // Check if an issue is known
 * if (KnownIssuesRegistry::isKnown('Client', 'unselectable_field', 'image_thumb_large')) {
 *     // Suppress error output - this is expected behavior
 * }
 *
 * // Record that we encountered a known issue (for summary)
 * KnownIssuesRegistry::recordEncountered('Client', 'unselectable_field', 'image_thumb_large');
 *
 * // Record that a known issue is now resolved (API behavior changed)
 * KnownIssuesRegistry::recordResolved('Client', 'unselectable_field', 'image_thumb_large');
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Tests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests;

class KnownIssuesRegistry
{
    /**
     * Registry of known issues.
     *
     * Structure:
     * [
     *     'resource' => [
     *         'issue_type' => [
     *             'specific_item' => [
     *                 'description' => 'Why this is expected',
     *                 'handled_by' => 'How SDK handles it (e.g., UNSELECTABLE constant)',
     *                 'reference' => 'Documentation reference (e.g., OVERRIDES.md#override-013)',
     *                 'added' => 'Date added to registry',
     *             ]
     *         ]
     *     ]
     * ]
     */
    private static array $knownIssues = [
        // =====================================================================
        // UNSELECTABLE FIELDS
        // These fields exist in API responses but cause HTTP 400 when explicitly
        // selected. Handled by UNSELECTABLE constant in resource classes.
        // Reference: OVERRIDES.md#override-013
        // =====================================================================
        'Client' => [
            'unselectable_field' => [
                'image_thumb_large' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'Client::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'image_thumb_medium' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'Client::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'image_thumb_small' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'Client::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
            ],
        ],
        'File' => [
            'unselectable_field' => [
                'image_thumb_large' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'File::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'image_thumb_medium' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'File::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'image_thumb_small' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'File::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
            ],
        ],
        'User' => [
            'unselectable_field' => [
                'date_format' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'time_format' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'decimal_sep' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'thousands_sep' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-07',
                ],
                'has_submitted_review' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-08',
                ],
                'image_thumb_large' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-08',
                ],
                'image_thumb_medium' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-08',
                ],
                'image_thumb_small' => [
                    'description' => 'Field exists in response but causes HTTP 400 when explicitly selected',
                    'handled_by' => 'User::UNSELECTABLE constant',
                    'reference' => 'OVERRIDES.md#override-013',
                    'added' => '2024-12-08',
                ],
            ],
        ],

        // =====================================================================
        // INVALID INCLUDES
        // Include relations documented or present but cause API errors
        // =====================================================================
        'InvoiceItem' => [
            'invalid_include' => [
                'expense' => [
                    'description' => 'Include relation exists in EntityMap but API rejects it with HTTP 400',
                    'handled_by' => 'Test catches error and continues - include not used in practice',
                    'reference' => 'API limitation - expense include not supported on invoice items',
                    'added' => '2024-12-07',
                ],
            ],
        ],

        // =====================================================================
        // API SERVER ERRORS (HTTP 500)
        // Intermittent server errors from Paymo API on certain include combinations
        // =====================================================================
        'Task' => [
            'server_error' => [
                'includes_combination' => [
                    'description' => 'Certain include combinations cause intermittent HTTP 500 errors',
                    'handled_by' => 'Test catches error and continues - not SDK issue',
                    'reference' => 'Paymo API server-side limitation',
                    'added' => '2024-12-07',
                ],
            ],
        ],
        'Discussion' => [
            'server_error' => [
                'includes_combination' => [
                    'description' => 'Certain include combinations cause intermittent HTTP 500 errors',
                    'handled_by' => 'Test catches error and continues - not SDK issue',
                    'reference' => 'Paymo API server-side limitation',
                    'added' => '2024-12-07',
                ],
            ],
        ],
        'Expense' => [
            'server_error' => [
                'includes_combination' => [
                    'description' => 'Certain include combinations cause intermittent HTTP 500 errors',
                    'handled_by' => 'Test catches error and continues - not SDK issue',
                    'reference' => 'Paymo API server-side limitation',
                    'added' => '2024-12-07',
                ],
            ],
        ],

        // =====================================================================
        // ACCESS DENIED (HTTP 403)
        // Resources that require specific account permissions
        // =====================================================================
        'Booking' => [
            'access_denied' => [
                'listing' => [
                    'description' => 'Booking listing requires specific account permissions/plan',
                    'handled_by' => 'Test skips when 403 received - account-specific limitation',
                    'reference' => 'Paymo API requires Resource Planning feature enabled',
                    'added' => '2024-12-07',
                ],
            ],
        ],
    ];

    /**
     * Track which known issues were encountered during this test run
     */
    private static array $encounteredIssues = [];

    /**
     * Track issues that were known but are now resolved (API fixed)
     */
    private static array $resolvedIssues = [];

    /**
     * Track NEW issues discovered during testing (not in registry)
     */
    private static array $newIssues = [];

    /**
     * Check if an issue is known and handled.
     *
     * @param string $resource The resource name (e.g., 'Client', 'User')
     * @param string $issueType The type of issue (e.g., 'unselectable_field', 'invalid_include')
     * @param string $specificItem The specific item (e.g., 'image_thumb_large', 'expense')
     * @return bool True if this is a known, handled issue
     */
    public static function isKnown(string $resource, string $issueType, string $specificItem): bool
    {
        return isset(self::$knownIssues[$resource][$issueType][$specificItem]);
    }

    /**
     * Get details about a known issue.
     *
     * @param string $resource
     * @param string $issueType
     * @param string $specificItem
     * @return array|null Issue details or null if not known
     */
    public static function getIssueDetails(string $resource, string $issueType, string $specificItem): ?array
    {
        return self::$knownIssues[$resource][$issueType][$specificItem] ?? null;
    }

    /**
     * Record that a known issue was encountered during testing.
     * This is used for the summary to show which known issues were hit.
     *
     * @param string $resource
     * @param string $issueType
     * @param string $specificItem
     * @param string $context Additional context about when it was encountered
     */
    public static function recordEncountered(string $resource, string $issueType, string $specificItem, string $context = ''): void
    {
        $key = "{$resource}::{$issueType}::{$specificItem}";
        if (!isset(self::$encounteredIssues[$key])) {
            self::$encounteredIssues[$key] = [
                'resource' => $resource,
                'issue_type' => $issueType,
                'item' => $specificItem,
                'details' => self::getIssueDetails($resource, $issueType, $specificItem),
                'contexts' => [],
            ];
        }
        if ($context && !in_array($context, self::$encounteredIssues[$key]['contexts'])) {
            self::$encounteredIssues[$key]['contexts'][] = $context;
        }
    }

    /**
     * Record that a previously known issue is now resolved.
     * This means the API behavior has changed and we should update our SDK.
     *
     * @param string $resource
     * @param string $issueType
     * @param string $specificItem
     * @param string $context
     */
    public static function recordResolved(string $resource, string $issueType, string $specificItem, string $context = ''): void
    {
        $key = "{$resource}::{$issueType}::{$specificItem}";
        self::$resolvedIssues[$key] = [
            'resource' => $resource,
            'issue_type' => $issueType,
            'item' => $specificItem,
            'details' => self::getIssueDetails($resource, $issueType, $specificItem),
            'context' => $context,
        ];
    }

    /**
     * Record a NEW issue that's not in the known issues registry.
     * These need investigation.
     *
     * @param string $resource
     * @param string $issueType
     * @param string $specificItem
     * @param string $errorMessage The actual error message received
     * @param string $context
     */
    public static function recordNew(string $resource, string $issueType, string $specificItem, string $errorMessage, string $context = ''): void
    {
        $key = "{$resource}::{$issueType}::{$specificItem}";
        if (!isset(self::$newIssues[$key])) {
            self::$newIssues[$key] = [
                'resource' => $resource,
                'issue_type' => $issueType,
                'item' => $specificItem,
                'error' => $errorMessage,
                'contexts' => [],
            ];
        }
        if ($context && !in_array($context, self::$newIssues[$key]['contexts'])) {
            self::$newIssues[$key]['contexts'][] = $context;
        }
    }

    /**
     * Check an HTTP error against known issues and record appropriately.
     *
     * @param string $resource Resource name
     * @param int $httpCode HTTP response code
     * @param string $errorMessage Error message from API
     * @param string $context Context (e.g., 'propertySelection', 'includes')
     * @return bool True if this is a known issue (suppress output), false if new
     */
    public static function checkAndRecord(string $resource, int $httpCode, string $errorMessage, string $context = ''): bool
    {
        // Determine issue type and specific item from error
        $issueType = self::determineIssueType($httpCode, $errorMessage);
        $specificItem = self::extractSpecificItem($errorMessage, $context);

        // Check if known
        if (self::isKnown($resource, $issueType, $specificItem)) {
            self::recordEncountered($resource, $issueType, $specificItem, $context);
            return true;
        }

        // Check for generic known issues (like server_error includes_combination)
        if ($httpCode === 500 && self::isKnown($resource, 'server_error', 'includes_combination')) {
            self::recordEncountered($resource, 'server_error', 'includes_combination', $context);
            return true;
        }

        // Check for access denied
        if ($httpCode === 403 && self::isKnown($resource, 'access_denied', 'listing')) {
            self::recordEncountered($resource, 'access_denied', 'listing', $context);
            return true;
        }

        // Not known - record as new
        self::recordNew($resource, $issueType, $specificItem, $errorMessage, $context);
        return false;
    }

    /**
     * Determine issue type from HTTP code and error message.
     */
    private static function determineIssueType(int $httpCode, string $errorMessage): string
    {
        if ($httpCode === 400) {
            if (strpos($errorMessage, 'Unknown field') !== false) {
                return 'unselectable_field';
            }
            if (strpos($errorMessage, 'reference') !== false) {
                return 'invalid_include';
            }
            return 'bad_request';
        }
        if ($httpCode === 403) {
            return 'access_denied';
        }
        if ($httpCode === 500) {
            return 'server_error';
        }
        return 'unknown';
    }

    /**
     * Extract the specific item (field name, include name) from error message.
     */
    private static function extractSpecificItem(string $errorMessage, string $context): string
    {
        // Try to extract field/reference name from error message
        // Pattern: "Unknown field or reference: `field_name`"
        if (preg_match('/`([^`]+)`/', $errorMessage, $matches)) {
            return $matches[1];
        }

        // Fallback to context
        return $context ?: 'unknown';
    }

    /**
     * Get all encountered known issues.
     */
    public static function getEncounteredIssues(): array
    {
        return self::$encounteredIssues;
    }

    /**
     * Get all resolved issues (previously known, now working).
     */
    public static function getResolvedIssues(): array
    {
        return self::$resolvedIssues;
    }

    /**
     * Get all new issues (need investigation).
     */
    public static function getNewIssues(): array
    {
        return self::$newIssues;
    }

    /**
     * Check if there are any new issues that need attention.
     */
    public static function hasNewIssues(): bool
    {
        return !empty(self::$newIssues);
    }

    /**
     * Check if there are any resolved issues (API behavior changed).
     */
    public static function hasResolvedIssues(): bool
    {
        return !empty(self::$resolvedIssues);
    }

    /**
     * Clear all recorded issues (call at start of test run).
     */
    public static function clear(): void
    {
        self::$encounteredIssues = [];
        self::$resolvedIssues = [];
        self::$newIssues = [];
    }

    /**
     * Get a summary of the known issues registry.
     */
    public static function getSummary(): array
    {
        $totalKnown = 0;
        foreach (self::$knownIssues as $resource => $types) {
            foreach ($types as $type => $items) {
                $totalKnown += count($items);
            }
        }

        return [
            'total_known' => $totalKnown,
            'encountered' => count(self::$encounteredIssues),
            'resolved' => count(self::$resolvedIssues),
            'new' => count(self::$newIssues),
        ];
    }

    /**
     * Get all known issues for reference.
     */
    public static function getAllKnownIssues(): array
    {
        return self::$knownIssues;
    }
}
