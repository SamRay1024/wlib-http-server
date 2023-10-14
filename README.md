# wlib/http-server

Kit léger pour gérer la couche serveur HTTP : la requête courante et sa réponse, les sessions.

## Installation

```shell
composer require wlib/http-server
```

## Classes disponibles

## \wlib\Http\Server\Request

Représente la requête HTTP courante.

```php
$request = new wlib\Http\Server\Request();
```

### Methodes disponibles

```php
public function getRawInput(): string;
public function getHeader(string $sKeyName, string $sDefault = null): ?string;
public function getHeaders(): array;
public function normalizeHeaderName(string $sName): string;
public function getServer(string $sKey, string $sDefault = null, int $iFilter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, mixed $mOptions = null);
public function getHost(): string;
public function getBrowser(): string;
public function getOS(): string;
public function getContentLength(): int;
public function getContentType(): ?string;
public function getContentCharset(): ?string;
public function getAcceptEncoding(): ?string;
public function getReferer(): ?string;
public function getIP(): ?string;
public function getPort(): int;
public function getMethod(): ?string;
public function getOriginalMethod(): string;
public function getPathInfo(): ?string;
public function getQueryString(): ?string;
public function getRequestUri(): ?string;
public function getScheme(): string;
public function getScriptName(): string;
public function getUserAgent(): string;
public function isAjax(): bool;
public function isDelete(): bool;
public function isHead(): bool;
public function isGet(): bool;
public function isOptions(): bool;
public function isPost(): bool;
public function isPut(): bool;
public function isPatch(): bool;
public function isGetOverPost(): bool;
public function isFormData(): bool;
public function isJson(): bool;
public function wantsJson(): bool;
public function stripSlashesIfNeeded(array|string $mData): array|string;
public function stripSlashes(array|string $mData): array|string;
public function inputs(string|int $mKey = null, mixed $mDefault = null): mixed;
public function get(string|int $mKey = null, mixed $mDefault = null): mixed;
public function post(string|int $mKey = null, mixed $mDefault = null): mixed;
public function hasGet(string|int $mKey): bool;
public function hasPost(string|int $mKey): bool;
```

## \wlib\Http\Server\Response

Représente la réponse à la requête HTTP courante.

```php
$response = new wlib\Http\Server\Response(
	new wlib\Http\Server\Request()
);
```

### Methodes disponibles

```php
public function __construct(Request $request);
public function __toString();
public function setStatus($iCode);
public function setLastModified($iTime);
public function setExpires($iTime);
public function setHeader($sName, $sValue, $bOverwrite = true);
public function setHeaders(array $aHeaders, $bOverwrite = false);
public function getHeaders();
public function getStatus();
public function getStatusString();
public function getHeadersString();
public function setBody($sBody, $bReplace = false);
public function sendHeaders();
public function send();
public function getLength();
public function getBody();
public function getBodyString();
public function hasBody();
public function addHeader($sName, $sValue);
public function addHeaders(array $aHeaders);
public function push($sContent);
public function replace($sContent);
public function flush($sContent = '', $iCode = null);
public function redirect($sURL, $iStatusCode = 307);
public function html($sContent, $iStatusCode = 200);
public function json(array $aData, $iStatusCode = 200);
```

## \wlib\Http\Server\Session

Pour manipuler la session PHP courante.

```php
$session = new wlib\Http\Server\Session(
	new wlib\Http\Server\Request()
);
```

### Méthodes disponibles

```php
public function __construct(Request $request);
public function start(array $aOptions = []);
public function setSessionTimeout(int|string $mTimeout): void;
public function isStarted(): bool;
public function close();
public function destroy();
public function enableCookie();
public function get(string|array $mKey, mixed $mDefault = null): mixed;
public function getId(): string;
public function getPath(): string;
public function getSessionTimeout(): int;
public function regenerateId();
public function reset();
public function setToken(string $sPrivateKey, string $uid = null);
public function checkToken(string $sPrivateKey, string $uid = null): bool;
public function set(string $sVarName, mixed $mValue);
public function setPath(string $sPath);
```