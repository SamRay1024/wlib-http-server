<?php

/* ==== LICENCE AGREEMENT =====================================================
 *
 * © Cédric Ducarre (20/05/2010)
 * 
 * wlib is a set of tools aiming to help in PHP web developpement.
 * 
 * This software is governed by the CeCILL license under French law and
 * abiding by the rules of distribution of free software. You can use, 
 * modify and/or redistribute the software under the terms of the CeCILL
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 * 
 * As a counterpart to the access to the source code and rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty and the software's author, the holder of the
 * economic rights, and the successive licensors have only limited
 * liability.
 * 
 * In this respect, the user's attention is drawn to the risks associated
 * with loading, using, modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean that it is complicated to manipulate, and that also
 * therefore means that it is reserved for developers and experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or 
 * data to be ensured and, more generally, to use and operate it in the 
 * same conditions as regards security.
 * 
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 * 
 * ========================================================================== */

namespace wlib\Http\Server;

use LogicException;
use RuntimeException;

/**
 * Session class handler.
 *
 * @author Cédric Ducarre
 * @since 19/07/2011
 */
class Session
{
	const KEY_TYPE		= '_type';
	const KEY_EXPIRE	= '_expire';
	const KEY_TOKENS	= '_tokens';
	const KEY_MESSAGES	= '_messages';

	const FLASH_INFO	= 'info';
	const FLASH_SUCCESS	= 'success';
	const FLASH_WARNING	= 'warning';
	const FLASH_ERROR	= 'error';

	/**
	 * Http server request.
	 * @var \wlib\Http\Server\Request
	 */
	protected $request = null;

	/**
	 * Session default options.
	 * @see https://www.php.net/manual/fr/session.configuration.php
	 * @var array
	 */
	private array $aOptions = [
		'use_strict_mode' => 1,
		'use_cookies' => 1,
		'use_only_cookies' => 1,
		// 'cookie_secure' => 1,
		'cookie_httponly' => 1,
		'cookie_samesite' => 'Strict',
		'use_trans_sid' => 0,
		'sid_length' => 48,
		'sid_bits_per_character' => 5
	];

	/**
	 * Session flag state.
	 * @var boolean
	 */
	private bool $bStarted = false;

	/**
	 * Session timeout (seconds).
	 * @var integer
	 */
	private ?int $iSessionTimeout = 10800;

	/**
	 * @param \wlib\Http\Server\Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Start the session.
	 * 
	 * - Start the session with options set,
	 * - Set a `$_SESSION['.expire']` value to handle expiration,
	 * 
	 * @param array $aOptions Array of options passed to session_start(). Override default options.
	 */
	public function start(array $aOptions = [])
	{
		if (!$this->bStarted)
		{
			arrayExtend($this->aOptions, $aOptions);
			$this->bStarted = session_start($this->aOptions);

			if (!$this->bStarted)
				throw new \Exception('Unable to start session. Check your options.');
		}

		$iNow = time();

		if (isset($_SESSION[self::KEY_EXPIRE]) && $_SESSION[self::KEY_EXPIRE] < $iNow)
		{
			$_SESSION = [];
			$this->regenerateId();
		}

		$_SESSION[self::KEY_TYPE] = 'normal';
		$_SESSION[self::KEY_EXPIRE] = $iNow + $this->iSessionTimeout;

		register_shutdown_function([$this, 'close']);
	}

	/**
	 * Set session timeout.
	 *
	 * @throws LogicException if session is already started.
	 * @param integer|string $mTimeout Session timout (minutes or string for `strtotime()`).
	 */
	public function setSessionTimeout(int|string $mTimeout): void
	{
		$this->haltIfStarted();
		
		$iSeconds = (is_numeric($mTimeout)
			? (int) $mTimeout * 60
			: strtotime($mTimeout) - time()
		);
		
		$this->iSessionTimeout = $iSeconds;
	}

	/**
	 * Is the session started ?
	 * 
	 * @return bool
	 */
	public function isStarted(): bool
	{
		return ($this->bStarted && session_status() === PHP_SESSION_ACTIVE);
	}

	/**
	 * Close the session.
	 * 
	 * Closes the session without destroying cookie.
	 */
	public function close()
	{
		if (session_status() === PHP_SESSION_ACTIVE)
		{
			session_write_close();
			$this->bStarted = false;
		}
	}

	/**
	 * Destroy all session data.
	 * 
	 * @throws LogicException if session is already started.
	 */
	public function destroy()
	{
		$this->haltIfStarted();

		$_SESSION = [];

		if (isset($_COOKIE[session_name()]))
		{
			$aParams = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 43200,
				$aParams['path'],
				$aParams['domain'],
				$aParams['secure']
			);
		}

		session_destroy();
	}

	/**
	 * Activate the cookie session.
	 * 
	 * Starts the session.
	 * 
	 * @throws LogicException if session timeout not set.
	 */
	public function enableCookie()
	{
		if ($this->iSessionTimeout === null)
			throw new LogicException('Session timeout not set.');

		$aCookieParams = session_get_cookie_params();

		session_set_cookie_params(
			$this->iSessionTimeout,
			$aCookieParams['path'],
			$aCookieParams['domain'],
			$aCookieParams['secure']
		);

		$this->start();

		$_SESSION[self::KEY_TYPE] = 'cookie';

		$this->regenerateId();
	}

	/**
	 * Check if a key exists in session.
	 * 
	 * @see session() from wlib/utils
	 * @throws LogicException if session is not started.
	 * @param string|int $mKey Key to check.
	 * @return bool
	 */
	public function has(string|int $mKey): bool
	{
		$this->haltIfNotStarted();

		return isArrayKey($_SESSION, $mKey);
	}

	/**
	 * Get a value from session.
	 * 
	 * @see session() from wlib/utils
	 * @throws LogicException if session is already started.
	 * @param string|int $mKey Key to access.
	 * @param mixed $mDefault Default value if `$mKey` does not exists.
	 * @return mixed
	 */
	public function get(string|int $mKey, mixed $mDefault = null): mixed
	{
		$this->haltIfNotStarted();

		return session($mKey, $mDefault);
	}

	/**
	 * Get current session ID.
	 *
	 * @return string
	 */
	public function getId(): string
	{
		return session_id();
	}

	/**
	 * Get path where session are stored.
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return session_save_path();
	}

	/**
	 * Get session timeout value.
	 *
	 * @return integer Timeout in minutes.
	 */
	public function getSessionTimeout(): int
	{
		return $this->iSessionTimeout / 60;
	}

	/**
	 * Regenerate session ID.
	 */
	public function regenerateId()
	{
		session_regenerate_id();
	}

	/**
	 * Reset session.
	 * 
	 * Destroys and closes current session.
	 */
	public function reset()
	{
		$this->destroy();
		$this->close();
	}

	/**
	 * Write in session.
	 *
	 * @param string|int $mVarName Variable name.
	 * @param mixed $mValue Value to save.
	 */
	public function set(string|int $mVarName, mixed $mValue)
	{
		$this->haltIfNotStarted();

		session([$mVarName => $mValue]);
	}

	/**
	 * Set the session save path.
	 *
	 * @param string $sPath Path of save folder.
	 */
	public function setPath(string $sPath)
	{
		$this->haltIfStarted();
		
		if (is_file($sPath))
			$sPath = dirname($sPath);

		if (!file_exists($sPath))
			throw new RuntimeException('Path "'.$sPath.'" does not exists.');

		if (!is_writable($sPath))
			throw new RuntimeException('Path "'. $sPath .'" is not writeable.');

		session_save_path($sPath);
	}

	/**
	 * Get a random token for CSRF protection.
	 * 
	 * Token is generated on first call and returned on subsequent calls.
	 * 
	 * @param string $sName A string to uniquely identify the token.
	 * @return string
	 */
	public function getToken(string $sName): string
	{
		$this->haltIfNotStarted();

		if (!isset($_SESSION[self::KEY_TOKENS]))
			$_SESSION[self::KEY_TOKENS] = [];

		if (!isset($_SESSION[self::KEY_TOKENS][$sName]))
			$_SESSION[self::KEY_TOKENS][$sName] = $this->createToken();

		return $_SESSION[self::KEY_TOKENS][$sName];
	}

	/**
	 * Remove a token.
	 * 
	 * @param string $sTokenId Token ID to remove.
	 * @return string|null Token value if it exits.
	 */
	public function removeToken(string $sName): ?string
	{
		$this->haltIfNotStarted();

		$sTokenKey = self::KEY_TOKENS .'.'. $sName;
		$sTokenValue = session($sTokenKey);
		unsession($sTokenKey);

		return $sTokenValue;
	}

	/**
	 * Generate a new value for a token.
	 * 
	 * @param string $sName Token identifier.
	 * @return string
	 */
	public function refreshToken(string $sName): string
	{
		$this->haltIfNotStarted();

		$_SESSION[self::KEY_TOKENS][$sName] = $this->createToken();

		return $_SESSION[self::KEY_TOKENS][$sName];
	}

	/**
	 * Check if a given value equals to the one generated for the given token identifier.
	 * 
	 * @param string $sName Token identifier.
	 * @param string $sTokenValue Value to check.
	 * @return bool
	 */
	public function isValidToken(string $sName, string $sTokenValue): bool
	{
		$this->haltIfNotStarted();

		$sSessionValue = session(self::KEY_TOKENS .'.'. $sName);

		if (is_null($sSessionValue))
			return false;

		return hash_equals($sSessionValue, $sTokenValue);
	}

	/**
	 * Get or set a flash message.
	 * 
	 * - $mMessage not empty = create the message
	 * - $mMessage empty = get and delete the message
	 * 
	 * A flash message is an array of two elements : 'message' and 'level'.
	 * 
	 * It's up to you to use the 'level' element to display the message.
	 * 
	 * @param string $sName Message name.
	 * @param string|array $mMessage Message content or array of message content + data.
	 * @param string $sLevel Message level.
	 * @return array|bool
	 */
	public function flash(string $sName, string|array $mMessage = '', string $sLevel = self::FLASH_INFO): array|bool
	{
		$this->haltIfNotStarted();

		if ($sName === '')
			throw new RuntimeException('Please provide a name to your flash message.');

		$sFlashKey = self::KEY_MESSAGES .'.'. $sName;

		if ((is_string($mMessage) && $mMessage !== '') ||
			(is_array($mMessage) && count($mMessage))
		) {
			unsession($sFlashKey);

			if (is_string($mMessage))
				$mMessage = [$mMessage];

			session([$sFlashKey => [
				'level'	=> $sLevel,
				'message' => array_shift($mMessage),
				'data' => $mMessage[0]
			]]);

			return true;
		}
		else
		{
			$mFlash = session($sFlashKey, false);
			unsession($sFlashKey);
			return $mFlash;
		}
	}

	/**
	 * Create a random token (for CSRF protection).
	 * 
	 * @return string
	 */
	private function createToken(): string
	{
		return bin2hex(random_bytes(32));
	}

	/**
	 * Halt execution if session is not started.
	 * 
	 * @throws LogicException if session not started.
	 */
	private function haltIfNotStarted(): void
	{
		if (!$this->isStarted())
			throw new LogicException('Session must be started to proceed.');
	}

	/**
	 * Halt execution if session is already started.
	 * 
	 * @throws LogicException if session is started.
	 */
	private function haltIfStarted(): void
	{
		if ($this->isStarted())
			throw new LogicException('Session can\'t be started to proceed.');
	}
}