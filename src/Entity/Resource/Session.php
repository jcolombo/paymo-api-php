<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 4:05 PM
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
 * SESSION RESOURCE - PAYMO API AUTHENTICATION SESSIONS
 * ======================================================================================
 *
 * This resource class represents a Paymo API session. Sessions are authentication
 * tokens created when users log in via password authentication (rather than API keys).
 * They track user access, IP addresses, and have expiration times.
 *
 * RESTRICTED OPERATIONS:
 * ----------------------
 * This resource has restricted operations:
 * - fetch(): Supported - retrieve session details
 * - list(): Supported - list user sessions (use SessionCollection)
 * - create(): Supported - create new session (authenticate)
 * - delete(): Supported - terminate/logout session
 * - update(): NOT SUPPORTED - sessions cannot be modified
 *
 * KEY FEATURES:
 * -------------
 * - User authentication via password
 * - IP address tracking
 * - Session expiration management
 * - Logout/session termination
 * - Read-only after creation
 *
 * UNIQUE CHARACTERISTICS:
 * -----------------------
 * - id: String type (not integer) - session tokens
 * - All properties are read-only
 * - No include types available
 * - No required creation properties (uses auth headers)
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * All Properties (read-only):
 * - id: Session token (text, NOT integer)
 * - user_id: Authenticated user
 * - ip: Client IP address
 * - expires_on: Expiration timestamp
 * - created_on: Creation timestamp
 * - updated_on: Last update timestamp
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Session;
 * use Jcolombo\PaymoApiPhp\Entity\Collection\SessionCollection;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Fetch a session by token
 * $session = Session::fetch($connection, 'abc123session-token');
 *
 * // Check session details
 * echo "User: " . $session->user_id . "\n";
 * echo "IP: " . $session->ip . "\n";
 * echo "Expires: " . $session->expires_on . "\n";
 *
 * // List all sessions for current user
 * $sessions = SessionCollection::list($connection);
 *
 * // Terminate/logout a session
 * $session->delete();
 *
 * // WILL THROW EXCEPTION:
 * // $session->update();  // Sessions cannot be modified
 * ```
 *
 * SESSION VS API KEY:
 * -------------------
 * There are two authentication methods for Paymo:
 * 1. API Key: Permanent key from account settings (recommended for apps)
 * 2. Session: Temporary token from password login (used by web interface)
 *
 * This SDK primarily uses API key authentication. Session resources are
 * for managing login sessions programmatically (e.g., building custom UIs).
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        User Authenticated user
 * @see        SessionCollection Session listing
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Exception;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use RuntimeException;

/**
 * Paymo Session resource for authentication management.
 *
 * Sessions represent authenticated user connections to the Paymo API.
 * This class supports create, fetch, list, and delete operations.
 * Update is not supported - sessions are immutable after creation.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property string $id         Session token (read-only, text type)
 * @property int    $user_id    Authenticated user ID (read-only)
 * @property string $ip         Client IP address (read-only)
 * @property string $expires_on Session expiration timestamp (read-only)
 * @property string $created_on Creation timestamp (read-only)
 * @property string $updated_on Last update timestamp (read-only)
 */
class Session extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Session';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'session';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'sessions';

    /**
     * Properties required when creating a new session.
     *
     * Empty because session creation uses authentication headers
     * (username/password) rather than JSON body properties.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = [];

    /**
     * Properties that cannot be modified via API.
     *
     * All session properties are read-only - sessions are immutable
     * after creation and can only be deleted (terminated).
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'ip', 'user_id', 'expires_on'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Empty because all properties come from the authentication process.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Sessions have no include types - they are standalone entities
     * that only reference the authenticated user via user_id.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [];

    /**
     * Property type definitions for validation and hydration.
     *
     * Note: 'id' is 'text' type (session token), not 'integer'.
     * This is unique among Paymo resources.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'         => 'text',
      'created_on' => 'datetime',
      'updated_on' => 'datetime',
      'ip'         => 'text',
      'user_id'    => 'resource:user',
      'expires_on' => 'datetime'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for sessions.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Update operation is not supported for Session.
     *
     * Sessions are immutable after creation. Once authenticated, a session
     * cannot be modified - it can only be deleted (terminated/logged out).
     *
     * @param array $options Update options (ignored)
     *
     * @throws Exception Always throws - sessions cannot be updated
     * @return void
     */
    public function update($options = []) : void
    {
        throw new RuntimeException("Session resources cannot be updated");
    }
}