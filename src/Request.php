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

/**
 * Http server request.
 *
 * @author Cédric Ducarre
 * @since 24/01/2013
 */
class Request
{
	/**
	 * Array of headers which doesn't follow "HTTP_X" naming convention.
	 * @var array
	 */
	private array $aSpecialHeaders = [
		'CONTENT_TYPE', 'CONTENT_LENGTH',
		'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE'
	];

	/**
	 * Headers already read.
	 * @var array
	 */
	private array $aHeaders = [];

	/**
	 * Secure server values.
	 * @var array
	 */
	private array $aServer = [];

	/**
	 * Current HTTP method, possibly overloaded.
	 * @var string
	 */
	private string $sMethod = '';

	/**
	 * GET parameters (also named "param").
	 * @var array
	 */
	private ?array $aGet = null;

	/**
	 * POST parameters (alsa named "data").
	 * @var array
	 */
	private ?array $aPost = null;

	/**
	 * Content of "php://input".
	 * @var string
	 */
	private ?string $sRawInput = null;

	/**
	 * Get raw data of request body.
	 * 
	 * Ie, reading of "php://input" stream.
	 * 
	 * @return string
	 */
	public function getRawInput(): string
	{
		if (is_null($this->sRawInput))
			$this->sRawInput = file_get_contents('php://input');

		return $this->sRawInput;
	}

	/**
	 * Get the value of the given request header.
	 * 
	 * @param string $sKeyName Header name ('Referer', 'Content-Type', ...).
	 * @param string $sDefault Default value.
	 * @return string|null
	 */
	public function getHeader(string $sKeyName, string $sDefault = null): ?string
	{
		if (isset($this->aHeaders[$sKeyName]))
			return $this->aHeaders[$sKeyName];

		$sRealKeyName = str_replace('-', '_', strtoupper($sKeyName));

		if (strpos($sKeyName, 'X_') === false && !in_array($sKeyName, $this->aSpecialHeaders))
			$sRealKeyName = 'HTTP_'. $sRealKeyName;

		if ($mValue = $this->getServer($sRealKeyName))
		{
			$this->aHeaders[$sKeyName] = $mValue;
			return $this->aHeaders[$sKeyName];
		}

		return $sDefault;
	}

	/**
	 * Get HTTP headers.
	 * 
	 * @return array
	 */
	public function getHeaders(): array
	{
		if ($this->aHeaders)
			return $this->aHeaders;

		foreach ($_SERVER as $key => $value)
		{
			$value = filter_var(
				$value,
				FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				['flags' => [FILTER_FLAG_NO_ENCODE_QUOTES]]
			);

			if (strpos($key, 'HTTP_') === 0)
			{
				$this->aHeaders[$this->normalizeHeaderName(substr($key, 5))] = $value;
				continue 1;
			}

			if (strpos($key, 'X_') === 0 || in_array($key, $this->aSpecialHeaders))
			{
				$this->aHeaders[$this->normalizeHeaderName($key)] = $value;
				continue 1;
			}
		}

		return $this->aHeaders;
	}

	/**
	 * Normalize the given header name.
	 *
	 * - Replace spaces and underscores by a dash,
	 * - Remove "HTTP_" prefix,
	 * - Capitalize the first letter of all words.
	 *
	 * @param string $sName Header name to normalize.
	 * @return string
	 */
	public function normalizeHeaderName(string $sName): string
	{
		$sName = strtolower($sName);
		$sName = str_replace(['-', '_'], ' ', $sName);
		$sName = preg_replace('`^http `', '', $sName);
		$sName = ucwords($sName);
		$sName = str_replace(' ', '-', $sName);

		return $sName;
	}

	/**
	 * Get a value from $_SERVER array.
	 *
	 * First reading save the value for future access.
	 *
	 * Default filter applied is `FILTER_SANITIZE_FULL_SPECIAL_CHARS` with 
	 * `FILTER_FLAG_NO_ENCODE_QUOTES` option.
	 *
	 * @param string $sKey Server entry to read.
	 * @param string $sDefault Default value if `$sKey` does not exists.
	 * @param integer $iFilter Filter to apply.
	 * @param mixed $mOptions Filter options.
	 * @return string|null
	 */
	public function getServer(
		string $sKey,
		string $sDefault = null,
		int $iFilter = FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		mixed $mOptions = null
	): ?string
	{
		if (isArrayKey($this->aServer, $sKey))
			return arrayValue($this->aServer, $sKey, $sDefault);

		if ($iFilter == FILTER_SANITIZE_FULL_SPECIAL_CHARS && is_null($mOptions))
			$mOptions = ['flags' => array(FILTER_FLAG_NO_ENCODE_QUOTES)];

		// Workaround because of https://bugs.php.net/bug.php?id=49184
		if (filter_has_var(INPUT_SERVER, $sKey))
			$value = filter_input(INPUT_SERVER, $sKey, $iFilter, $mOptions);

		else
			$value = (isset($_SERVER[$sKey])
				? filter_var($_SERVER[$sKey], $iFilter, $mOptions)
				: false
			);

		$this->aServer[$sKey] = ($value !== false ? $value : $sDefault);

		return $this->aServer[$sKey];
	}

	/**
	 * Get Host header.
	 * 
	 * @return string
	 */
	public function getHost(): string
	{
		$sHost = $this->getHeader('Host');

		if (!is_null($sHost))
		{
			if (strpos($sHost, ':') !== false)
			{
				$aHostParts = explode(':', $sHost);
				return $aHostParts[0];
			}

			return $sHost;
		}
		else $this->getServer('SERVER_NAME');
	}

	/**
	 * Get the client browser.
	 * 
	 * @return string Empty string if unknown.
	 */
	public function getBrowser(): string
	{
		$sUA = strtolower($this->getHeader('User-Agent'));

		if (strpos($sUA, 'opera'))
			return 'Opera' . (strpos($sUA, 'mini') ? ' Mini' : '');
		if (strpos($sUA, 'chrome'))
			return 'Chrome';
		if (strpos($sUA, 'iphone'))
			return 'iPhone';
		if (strpos($sUA, 'safari'))
			return 'Safari';
		if (strpos($sUA, 'firefox'))
			return 'Firefox';
		if (strpos($sUA, 'msie'))
			return 'MSIE';

		return '';
	}

	/**
	 * Get client operating system.
	 * 
	 * @return string Empty string if unknown.
	 */
	public function getOS(): string
	{
		$sUA = $this->getHeader('User-Agent');

		// Computers
		if (strpos($sUA, 'Win'))		return 'Windows';
		if (strpos($sUA, 'Linux'))		return 'Linux';
		if (strpos($sUA, 'Mac'))		return 'Macintosh';

		$sUA = strtolower($sUA);

		// Phones and tablets
		if (strpos($sUA, 'iphone'))		return 'iPhone';
		if (strpos($sUA, 'ipad'))		return 'iPad';
		if (strpos($sUA, 'android'))	return 'Android';
		if (strpos($sUA, 'blackberry'))	return 'Blackberry';
		if (strpos($sUA, 'palm'))		return 'Palm';
		if (strpos($sUA, 'ipod'))		return 'iPod';

		// And others
		if (strpos($sUA, 'freebsd'))	return 'FreeBSD';
		if (strpos($sUA, 'openbsd'))	return 'OpenBSD';
		if (strpos($sUA, 'netbsd'))		return 'NetBSD';
		if (strpos($sUA, 'os/2'))		return 'OS/2';
		if (strpos($sUA, 'sunos'))		return 'Sunos';
		if (strpos($sUA, 'beos'))		return 'Beos';
		if (strpos($sUA, 'aix'))		return 'Aix';
		if (strpos($sUA, 'qnx'))		return 'QNX';

		return '';
	}

	/**
	 * Get content length.
	 * 
	 * @return integer
	 */
	public function getContentLength(): int
	{
		return (int) $this->getHeader('Content-Length');
	}

	/**
	 * Get content type.
	 * 
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->getHeader('Content-Type', '');
	}

	/**
	 * Get content charset.
	 *
	 * @return string
	 */
	public function getContentCharset(): string
	{
		$sContentType = $this->getHeader('Content-Type');

		if (!$sContentType)
			return '';

		$aMatches = array();

		return (preg_match('#charset\s*=([^;]+)#i', $sContentType, $aMatches) !== false
			? $aMatches[1]
			: ''
		);
	}

	/**
	 * Get accept encoding.
	 *
	 * @return string
	 */
	public function getAcceptEncoding(): string
	{
		return $this->getHeader('Accept-Encoding', '');
	}

	/**
	 * Get referer.
	 *
	 * @return string
	 */
	public function getReferer(): string
	{
		return $this->getHeader('Referer', '');
	}

	/**
	 * Get client IP address.
	 * 
	 * @return string
	 */
	public function getIP(): string
	{
		$aKeys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		];

		foreach ($aKeys as $sKey)
		{
			$sIP = $this->getServer(
				$sKey,
				null,
				FILTER_VALIDATE_IP,
				['flags' => [FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE]]
			);

			if ($sIP)
				return $sIP;
		}

		return '';
	}

	/**
	 * Get IP port.
	 * 
	 * @return integer
	 */
	public function getPort(): int
	{
		return (int) $this->getServer('SERVER_PORT');
	}

	/**
	 * Get current HTTP method.
	 *
	 * It can be overriden :
	 * 
	 * - from header "X-Http-Method-Override"
	 * - from $_POST['_method'] value.
	 *
	 * $_POST has priority if both values are sets.
	 *
	 * @return string
	 */
	public function getMethod(): string
	{
		if ($this->sMethod)
			return $this->sMethod;

		$this->sMethod = strtoupper($this->getOriginalMethod());

		if (isset($_POST['_method']))
			$this->sMethod = strtoupper(filter_input(
				INPUT_POST,
				'_method',
				FILTER_SANITIZE_FULL_SPECIAL_CHARS
			));
		elseif ($this->getHeader('X-Http-Method-Override'))
			$this->sMethod = strtoupper($this->getHeader('X-Http-Method-Override'));

		return $this->sMethod;
	}

	/**
	 * Get real HTTP method.
	 * 
	 * This method ignores HTTP method override.
	 *
	 * @return string
	 */
	public function getOriginalMethod(): string
	{
		return $this->getServer('REQUEST_METHOD', 'GET');
	}

	/**
	 * Get serveur PATH_INFO value.
	 * 
	 * @return string
	 */
	public function getPathInfo(): string
	{
		return $this->getServer('PATH_INFO', '');
	}

	/**
	 * Get server QUERY_STRING value.
	 * 
	 * @return string
	 */
	public function getQueryString(): string
	{
		return $this->getServer('QUERY_STRING', '');
	}

	/**
	 * Get server REQUEST_URI value.
	 * 
	 * @return string
	 */
	public function getRequestUri(): string
	{
		return $this->getServer('REQUEST_URI', '');
	}

	/**
	 * Get HTTP scheme (http ou https).
	 * 
	 * @return string
	 */
	public function getScheme(): string
	{
		return (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https');
	}

	/**
	 * Get server SCRIPT_NAME value.
	 * 
	 * @return string
	 */
	public function getScriptName(): string
	{
		return $this->getServer('SCRIPT_NAME', '');
	}

	/**
	 * Get User-Agent.
	 * 
	 * @return string
	 */
	public function getUserAgent(): string
	{
		return $this->getHeader('User-Agent', '');
	}

	/**
	 * Is-it an AJAX request ?
	 * 
	 * @return boolean
	 */
	public function isAjax(): bool
	{
		if ($this->getHeader('X-Requested-With') === 'XMLHttpRequest')
			return true;

		return false;
	}

	/**
	 * Is-it a DELETE request ?
	 * 
	 * @return boolean
	 */
	public function isDelete(): bool
	{
		return ($this->getMethod() === 'DELETE');
	}

	/**
	 * Is-it a HEAD request ?
	 * 
	 * @return boolean
	 */
	public function isHead(): bool
	{
		return ($this->getMethod() === 'HEAD');
	}

	/**
	 * Is-it a GET request ?
	 * 
	 * @return boolean
	 */
	public function isGet(): bool
	{
		return ($this->getMethod() === 'GET');
	}

	/**
	 * Is-it an OPTIONS request ?
	 * 
	 * @return boolean
	 */
	public function isOptions(): bool
	{
		return ($this->getMethod() === 'OPTIONS');
	}

	/**
	 * Is-it a POST request ?
	 * 
	 * @return boolean
	 */
	public function isPost(): bool
	{
		return ($this->getMethod() === 'POST');
	}

	/**
	 * Is-it a PUT request ?
	 * 
	 * @return boolean
	 */
	public function isPut(): bool
	{
		return ($this->getMethod() === 'PUT');
	}

	/**
	 * Is-it a PATCH request ?
	 * 
	 * @return boolean
	 */
	public function isPatch(): bool
	{
		return ($this->getMethod() === 'PATCH');
	}

	/**
	 * Is the real POST method overriden by GET ?
	 *
	 * @return boolean
	 */
	public function isGetOverPost(): bool
	{
		return ($this->getMethod() === 'GET' && $this->getOriginalMethod() === 'POST');
	}

	/**
	 * Is-it a form submission ?
	 *
	 * @return boolean
	 */
	public function isFormData(): bool
	{
		return (
			($this->getOriginalMethod() === 'POST' && is_null($this->getContentType()))
			|| stripos((string) $this->getContentType(), 'application/x-www-form-urlencoded') !== false
		);
	}

	/**
	 * Is-it a request with JSON data ?
	 *
	 * @return boolean
	 */
	public function isJson(): bool
	{
		return (stripos((string) $this->getContentType(), '/json') !== false);
	}

	/**
	 * Does the caller want a JSON response ?
	 *
	 * @return boolean
	 */
	public function wantsJson(): bool
	{
		return (stripos($this->getHeader('Accept', ''), 'application/json') === 0);
	}

	/**
	 * Remove backslashes if "magic_quotes_gpc" set to "on".
	 *
	 * @param array|string $mData
	 * @return array|string
	 */
	public function stripSlashesIfNeeded(array|string $mData): array|string
	{
		return (ini_get('magic_quotes_gpc') == '1'
			? $this->stripSlashes($mData)
			: $mData
		);
	}

	/**
	 * Remove backslashes of given variable.
	 *
	 * @param array|string $mData
	 * @return array|string
	 */
	public function stripSlashes(array|string $mData): array|string
	{
		return (is_array($mData)
			? array_map([$this, 'stripSlashes'], $mData)
			: stripslashes($mData)
		);
	}

	/**
	 * Init content of `$this->aGet`.
	 */
	private function initGet()
	{
		$array = [];

		function_exists('mb_parse_str')
			? mb_parse_str($this->getQueryString(), $array)
			: parse_str($this->getQueryString(), $array);

		$this->aGet = $this->stripSlashesIfNeeded($array);
	}

	/**
	 * Init content of `$this->aPost`.
	 */
	private function initPost()
	{
		if ($this->isJson() && $this->getRawInput() != '')
		{
			$array = json_decode($this->getRawInput(), true);

			if (!is_null($array))
			{
				$this->aPost = $this->stripSlashesIfNeeded($array);
				return;
			}
		}

		if ($this->isFormData() && is_string($this->getRawInput()))
		{
			$array = [];

			function_exists('mb_parse_str')
				? mb_parse_str($this->getRawInput(), $array)
				: parse_str($this->getRawInput(), $array);

			$this->aPost = $this->stripSlashesIfNeeded($array);
			return;
		}

		$this->aPost = $this->stripSlashesIfNeeded($_POST);
	}

	/**
	 * Get inputs.
	 *
	 * Inputs are a merge of $_GET and $_POST. $_GET has priority over $_POST.
	 *
	 * @param string|int $mKey Parameter key of null for getting all.
	 * @param mixed $mDefault Default value.
	 * @return mixed
	 */
	public function inputs(string|int $mKey = null, mixed $mDefault = null): mixed
	{
		if (is_null($this->aGet))
			$this->initGet();

		if (is_null($this->aPost))
			$this->initPost();

		if (!is_null($mKey))
		{
			$value = arrayValue($this->aGet, $mKey);

			if (is_null($value))
				$value = arrayValue($this->aPost, $mKey, $mDefault);

			return $value;
		}

		return $this->aGet + $this->aPost;
	}

	/**
	 * Get a value from $_GET.
	 *
	 * @param string|int $mKey Parameter key of null for getting all.
	 * @param mixed $mDefault Default value.
	 * @return mixed
	 */
	public function get(string|int $mKey = null, mixed $mDefault = null): mixed
	{
		if (is_null($this->aGet))
			$this->initGet();

		if ($this->isGetOverPost())
			return $this->inputs($mKey, $mDefault);

		if (!is_null($mKey))
			return arrayValue($this->aGet, $mKey, $mDefault);

		return $this->aGet;
	}

	/**
	 * Get a value from $_POST.
	 *
	 * @param string|int $mKey Parameter key of null for getting all.
	 * @param mixed $mDefault Default value.
	 * @return mixed
	 */
	public function post(string|int $mKey = null, mixed $mDefault = null): mixed
	{
		if (is_null($this->aPost))
			$this->initPost();

		if (!is_null($mKey))
			return arrayValue($this->aPost, $mKey, $mDefault);

		return $this->aPost;
	}

	/**
	 * Check if a $_GET value exists.
	 *
	 * @param string|int $mKey Key to check.
	 * @return boolean
	 */
	public function hasGet(string|int $mKey): bool
	{
		if (is_null($this->aGet))
			$this->initGet();

		return isArrayKey($this->aGet, $mKey);
	}

	/**
	 * Check if a $_POST value exists.
	 *
	 * @param string|int $mKey Key to check.
	 * @return boolean
	 */
	public function hasPost(string|int $mKey): bool
	{
		if (is_null($this->aPost))
			$this->initPost();

		return isArrayKey($this->aPost, $mKey);
	}
}