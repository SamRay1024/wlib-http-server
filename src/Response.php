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
 * Http server response.
 *
 * @author Cédric Ducarre
 * @since 24/01/2013
 */
class Response
{
	const HTTP_CONTINUE				= 100;
	const HTTP_SWITCHING_PROTOCOLS	= 101;
	const HTTP_PROCESSING			= 102;

	const HTTP_OK								= 200;
	const HTTP_CREATED							= 201;
	const HTTP_ACCEPTED							= 202;
	const HTTP_NON_AUTHORITATIVE_INFORMATION	= 203;
	const HTTP_NO_CONTENT						= 204;
	const HTTP_RESET_CONTENT					= 205;
	const HTTP_PARTIAL_CONTENT					= 206;
	const HTTP_MULTI_STATUS						= 207;
	const HTTP_ALREADY_REPORTED					= 208;
	const HTTP_IM_USED							= 226;

	const HTTP_MULTIPLE_CHOICES		= 300;
	const HTTP_MOVED_PERMANENTLY	= 301;
	const HTTP_FOUND				= 302;
	const HTTP_SEE_OTHER			= 303;
	const HTTP_NOT_MODIFIED			= 304;
	const HTTP_USE_PROXY			= 305;
	const HTTP_RESERVED				= 306;
	const HTTP_TEMPORARY_REDIRECT	= 307;
	const HTTP_PERMANENTLY_REDIRECT	= 308;

	const HTTP_BAD_REQUEST						= 400;
	const HTTP_UNAUTHORIZED						= 401;
	const HTTP_PAYMENT_REQUIRED					= 402;
	const HTTP_FORBIDDEN						= 403;
	const HTTP_NOT_FOUND						= 404;
	const HTTP_METHOD_NOT_ALLOWED				= 405;
	const HTTP_NOT_ACCEPTABLE					= 406;
	const HTTP_PROXY_AUTHENTICATION_REQUIRED	= 407;
	const HTTP_REQUEST_TIMEOUT					= 408;
	const HTTP_CONFLICT							= 409;
	const HTTP_GONE								= 410;
	const HTTP_LENGTH_REQUIRED					= 411;
	const HTTP_PRECONDITION_FAILED				= 412;
	const HTTP_REQUEST_ENTITY_TOO_LARGE			= 413;
	const HTTP_REQUEST_URI_TOO_LONG				= 414;
	const HTTP_UNSUPPORTED_MEDIA_TYPE			= 415;
	const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE	= 416;
	const HTTP_EXPECTATION_FAILED				= 417;
	const HTTP_I_AM_A_TEAPOT					= 418;
	const HTTP_UNPROCESSABLE_ENTITY				= 422;
	const HTTP_LOCKED							= 423;
	const HTTP_FAILED_DEPENDENCY				= 424;
	const HTTP_UPGRADE_REQUIRED					= 426;
	const HTTP_PRECONDITION_REQUIRED			= 428;
	const HTTP_TOO_MANY_REQUESTS				= 429;
	const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE	= 431;

	const HTTP_INTERNAL_SERVER_ERROR				= 500;
	const HTTP_NOT_IMPLEMENTED						= 501;
	const HTTP_BAD_GATEWAY							= 502;
	const HTTP_SERVICE_UNAVAILABLE					= 503;
	const HTTP_GATEWAY_TIMEOUT						= 504;
	const HTTP_VERSION_NOT_SUPPORTED				= 505;
	const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL	= 506;
	const HTTP_INSUFFICIENT_STORAGE					= 507;
	const HTTP_LOOP_DETECTED						= 508;
	const HTTP_NOT_EXTENDED							= 510;
	const HTTP_NETWORK_AUTHENTICATION_REQUIRED		= 511;

	/**
	 * Http server request.
	 * @var \wlib\Http\Request
	 */
	protected $request = null;

	/**
	 * HTTP status code
	 * @var integer
	 */
	protected int $iStatus = 200;

	/**
	 * HTTP headers.
	 * @var array
	 */
	protected array $aHeaders = array();

	/**
	 * Response body handle.
	 * @var resource
	 */
	protected $hBody = '';

	/**
	 * HTTP codes and messages array.
	 * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * @var array
	 */
	private static array $aStatusMessages = [

		// Réponses provisoires
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',						// WebDAV

		// Succès
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',						// WebDAV
		208 => 'Already Reported',					// WebDAV
		226 => 'IM Used',							// Obscure : http://tools.ietf.org/html/rfc3229

		// Redirections
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',

		// Erreurs client
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'Im a teapot',						// RFC humoristique : http://tools.ietf.org/html/rfc2324
		422 => 'Unprocessable Entity',				// WebDAV
		423 => 'Locked',							// WebDAV
		424 => 'Failed Dependency',					// WebDAV
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',

		// Erreurs serveur
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',				// WebDAV
		508 => 'Loop Detected',						// WebDAV
		510 => 'Not Extended',
		511 => 'Network Authentication Required'
	];

	/**
	 * @param \wlib\Http\Server\Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;

		$this->initBody();
	}

	/**
	 * Return string response.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getStatusString() ."\r\n"
			. $this->getHeadersString() ."\r\n"
			. $this->getBodyString();
	}

	/**
	 * Init the response body.
	 * 
	 * If content had already added to body, it will be lost.
	 * 
	 * Body is handled with fopen in RAM with a 8Mo max size before swapping.
	 */
	private function initBody()
	{
		if (is_resource($this->hBody))
			fclose($this->hBody);

		$this->hBody = fopen('php://temp/maxmemory:8388608', 'w+'); // 8 Mo
	}

	/**
	 * Set response status code.
	 *
	 * @param integer $iCode HTTP status code.
	 * @return self
	 */
	public function setStatus($iCode)
	{
		if ($iCode > 0)
			$this->iStatus = (int) $iCode;

		return $this;
	}

	/**
	 * Set "Last-Modified" header.
	 *
	 * Tells to the HTTP client to use the "If-Modified-Since" header in following GET
	 * requests on the resource.
	 * 
	 * If `$iTime` is equal to "If-Modified-Since" then a 304 code is returned.
	 *
	 * @param int $iTime UNIX timestamp of the last resource update.
	 * @return self
	 */
	public function setLastModified($iTime)
	{
		if (is_integer($iTime))
		{
			$this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $iTime));

			$sIfModifiedSince = $this->request->getHeader('IF_MODIFIED_SINCE');

			if (
				$this->request->isGet()
				&& $sIfModifiedSince
				&& $iTime === strtotime($sIfModifiedSince)
			)
				$this->setStatus(self::HTTP_NOT_MODIFIED)->send();
		}

		return $this;
	}

	/**
	 * Set the "Expires" header.
	 *
	 * Tells to the HTTP client to use its local cache until expiration of `$iTime`.
	 *
	 * @param int $iTime UNIX timestamp of the resource expiration datetime.
	 * @return self
	 */
	public function setExpires($iTime)
	{
		if (is_integer($iTime))
			$this->setHeader('Expires', gmdate('D, d M Y H:i:s T', $iTime));

		return $this;
	}

	/**
	 * Set an HTTP header value.
	 *
	 * @param string $sName Header's name.
	 * @param string $sValue Associated value.
	 * @param boolean $bOverwrite Allow overwrite if header is already set.
	 * @return self
	 */
	public function setHeader($sName, $sValue, $bOverwrite = true)
	{
		$sName		= $this->formatHeaderName($sName);
		$bExists	= isset($this->aHeaders[$sName]);

		if (!$bExists || ($bExists && $bOverwrite === true))
			$this->aHeaders[$sName] = $sValue;

		return $this;
	}

	/**
	 * Format header name.
	 *
	 * @param string $sName Header name.
	 * @return string
	 */
	public function formatHeaderName(string $sName): string
	{
		return ucwords(
			strtr(
				$sName,
				'_ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'-abcdefghijklmnopqrstuvwxyz'
			),
			'-'
		);
	}

	/**
	 * Set several HTTP headers values.
	 *
	 * @param array $aHeaders HTTP headers associative array.
	 * @param boolean $bOverwrite Allow overwrite of headers already set.
	 * @return self
	 */
	public function setHeaders(array $aHeaders, $bOverwrite = false)
	{
		foreach ($aHeaders as $name => $value)
			$this->setHeader($name, $value, $bOverwrite);

		return $this;
	}

	/**
	 * Get an HTTP header value.
	 * 
	 * @param string $sName Header name.
	 * @param string $sDefault Default value if header not set.
	 * @return string|null
	 */
	public function getHeader($sName, $sDefault = null): ?string
	{
		return $this->aHeaders[$this->formatHeaderName($sName)] ?? $sDefault;
	}
	
	/**
	 * Checks if given header name exists.
	 *
	 * @param string $sName Header name.
	 * @return bool
	 */
	public function hasHeader($sName): bool
	{
		return array_key_exists($this->formatHeaderName($sName), $this->aHeaders);
	}

	/**
	 * Get all HTTP headers set.
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->aHeaders;
	}

	/**
	 * Get the defined status code.
	 * 
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->iStatus;
	}

	/**
	 * Get the HTTP response status string.
	 *
	 * @return string
	 */
	public function getStatusString()
	{
		return 'HTTP/1.1 '. $this->iStatus .' '. $this->getStatusMessage($this->iStatus);
	}

	/**
	 * Get the given status code corresponding message.
	 *
	 * If code is undefined, the "500 Internal Server Error" code is used by default.
	 *
	 * @param integer $iCode HTTP status code.
	 * @return string Corresponding status message.
	 */
	private function getStatusMessage($iCode)
	{
		return arrayValue(self::$aStatusMessages, $iCode, self::$aStatusMessages[500]);
	}

	/**
	 * Get HTTP headers as string.
	 * 
	 * String format :
	 * 
	 * ```
	 * <Header-Name>: <header value>\r\n
	 * ...
	 * ```
	 *
	 * @return string
	 */
	public function getHeadersString()
	{
		$sHeaders = '';

		foreach ($this->aHeaders as $sName => $sValue)
			$sHeaders .= $sName .': '. $sValue ."\n";

		return $sHeaders;
	}

	/**
	 * Set the response body.
	 *
	 * By default, new content is appended to existing content.
	 *
	 * @param string $sBody Content to set.
	 * @param boolean $bReplace `true` to replace of append.
	 * @return self
	 */
	public function setBody($sBody, $bReplace = false)
	{
		if ($sBody != '')
		{
			if ($bReplace || !is_resource($this->hBody))
			{
				$this->initBody();
			}

			fwrite($this->hBody, $sBody);
		}

		return $this;
	}

	/**
	 * Send only HTTP headers.
	 */
	public function sendHeaders()
	{
		if (!headers_sent())
		{
			$this->preheat();

			header((strpos(PHP_SAPI, 'cgi') === 0
				? 'Status: ' . $this->iStatus . ' ' . $this->getStatusMessage($this->iStatus)
				: 'HTTP/1.1 ' . $this->iStatus . ' ' . $this->getStatusMessage($this->iStatus)
			));

			foreach ($this->aHeaders as $name => $value)
			{
				$values = explode("\n", $value);

				foreach ($values as $val)
					header("$name: $val", false);
			}
		}
	}

	/**
	 * Prepare response before sending it to the client.
	 */
	public function preheat()
	{
		if (!$this->hasHeader('Content-Type'))
		{
			$aAccept = explode(',', $this->request->getHeader('Accept'));
			$this->setHeader('Content-Type', $aAccept[0] ?? 'text/html');
		}

		if ($this->request->getMethod() == 'HEAD')
		{
			$iLength = $this->getLength();
			$this->initBody();
			$this->setHeader('Content-Length', $iLength);
		}
		else $this->setHeader('Content-Length', $this->getLength(), false);
	}

	/**
	 * Send response body to output buffer.
	 */
	public function sendBody()
	{
		rewind($this->hBody);
		fpassthru($this->hBody);
	}

	/**
	 * Send the response, headers and body (except for "HEAD" requests).
	 */
	public function send()
	{
		$this->sendHeaders();
		$this->sendBody();
	}

	/**
	 * Get the response body length.
	 *
	 * @return integer
	 */
	public function getLength()
	{
		return (is_resource($this->hBody) ? fstat($this->hBody)['size'] : 0);
	}

	/**
	 * Get the response body.
	 *
	 * It returns the file handle where data are stored. To get the real content,
	 * use `getBodyString()`.
	 *
	 * @return resource
	 */
	public function getBody()
	{
		return $this->hBody;
	}

	/**
	 * Get the response body content.
	 *
	 * @return string
	 */
	public function getBodyString()
	{
		if (is_resource($this->hBody))
		{
			rewind($this->hBody);
			return stream_get_contents($this->hBody);
		}

		return '';
	}

	/**
	 * Does the answer contains data ?
	 *
	 * @return boolean
	 */
	public function hasBody()
	{
		return ($this->getLength() > 0);
	}

	/**
	 * Add an HTTP header.
	 *
	 * To set several values for the same header, separate them with "\n".
	 *
	 * @param string $sName Header's name.
	 * @param string $sValue Associated value(s).
	 * @return self
	 */
	public function addHeader($sName, $sValue)
	{
		return $this->setHeader($sName, $sValue);
	}

	/**
	 * Add several HTTP headers.
	 *
	 * @param array $aHeaders HTTP headers associative array.
	 * @return self
	 */
	public function addHeaders(array $aHeaders)
	{
		return $this->setHeaders($aHeaders);
	}

	/**
	 * Complete response body.
	 *
	 * Doesn't send the response. To do so, call `flush()` or `send()`.
	 *
	 * Example :
	 * 
	 * ```php
	 * $oResponse->addHeader('Content-Type', 'text/html');
	 *
	 * $oResponse->push('<h1>Hello world !</h1>');
	 * $oResponse->push('<p>I am a paragraph...</p>');
	 *
	 * $oResponse->flush();
	 * ```
	 *
	 * @param string $sContent Content to add to body.
	 * @return self
	 */
	public function push($sContent)
	{
		return $this->setBody($sContent);
	}

	/**
	 * Replace response body.
	 *
	 * @param string $sContent New body content.
	 * @return self
	 */
	public function replace($sContent)
	{
		return $this->setBody($sContent, true);
	}

	/**
	 * Alternative function to send the response.
	 *
	 * Unlike `send()`, this method ensure that the "Content-Type" header is sent
	 * with "text/html" as default value.
	 * 
	 * ```php
	 * // Send a text/html 200 response (headers + body)
	 * $response->flush('<h1>Hello world !</h1>');
	 *
	 * // Or, send a 404 response
	 * $response->flush('<h1>Not found :-(</h1>', \wlib\Http\Response::HTTP_NOT_FOUND);
	 * ```
	 *
	 * @param string $sContent Content to add to response body.
	 * @param integer $iCode Replace status code is greater than 0.
	 */
	public function flush($sContent = '', $iCode = null)
	{
		if ($iCode > 0)
			$this->setStatus($iCode);

		$this
			->setHeader('Content-Type', 'text/html', false)
			->setBody($sContent)
			->send();
	}

	/**
	 * Send a redirect response.
	 *
	 * @param string $sURL Redirection URL.
	 * @param integer $iStatusCode Redirection status code.
	 */
	public function redirect($sURL, $iStatusCode = 307)
	{
		$this
			->setStatus($iStatusCode)
			->setHeader('Location', $sURL)
			->send();
	}

	/**
	 * Set a "text/html“ response.
	 * 
	 * @param string $sContent Body content.
	 * @param integer $iStatusCode HTTP status code.
	 * @return self
	 */
	public function html($sContent, $iStatusCode = 200)
	{
		return $this
			->setStatus($iStatusCode)
			->addHeader('Content-Type', 'text/html')
			->setBody($sContent);
	}

	/**
	 * Set an "application/json" response.
	 * 
	 * @param array $aData Array which will be JSON encoded.
	 * @param integer $iStatusCode HTTP status code.
	 * @return self
	 */
	public function json(array $aData, $iStatusCode = 200)
	{
		return $this
			->setStatus($iStatusCode)
			->addHeader('Content-Type', 'application/json')
			->setBody(json_encode($aData));
	}
}