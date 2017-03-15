<?php

/*
 * Copyright 2017 Florian Will.
 *
 * Licensed under the Universal Permissive License (UPL), Version 1.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://opensource.org/licenses/UPL
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Die NamiWrapper-Klasse dient dazu, Anfragen an die Nami durchzuführen.
 */
class NamiWrapper {
    CONST METHOD_POST = 'POST';
    CONST OPERATION_CREATE = self::METHOD_POST;
    CONST METHOD_PUT = 'PUT';
    CONST OPERATION_UPDATE = self::METHOD_PUT;
    CONST METHOD_GET = 'GET';
    CONST OPERATION_READ = self::METHOD_GET;
    CONST METHOD_DELETE = 'DELETE';
    CONST OPERATION_DELETE = self::METHOD_DELETE;
    
    CONST STATUS_CODE_OK = 0;
    CONST STATUS_CODE_EXPIRED = 3000;
    CONST STATUS_CODE_AUTH_ERR = 3001;
    CONST STATUS_CODE_EXCEPTION = 3002;
    CONST STATUS_CODE_UNKNOWN_ERR = 3099;
    
    CONST MIME_JSON = 'application/json';
    CONST MIME_URL_ENCODED = 'application/x-www-form-urlencoded';
    
    CONST ENCODING_JSON = 'json';
    CONST ENCODING_URL = 'url';
    CONST ENCODING_JSON_MANUAL = 'jsonManual';
    CONST ENCODING_NO_CONTENT = 'noContent';
    
    private $config = [];
    
    private $cookie;
    private $lastLoginResponse;
    
    /**
     * Konstruktor. Der Konstruktor kann auf drei Arten aufgerufen werden:
     * 1. Ohne Übergabe von Parametern: `new NamiWrapper()`. Login-Daten müssen dann
     *    der ´login´-Funktion übergeben werden.
     * 2. Mit Übergabe von Nutzername und Passwort: `new NamiWrapper('123456', 'password1234')`.
     *    Ein Login kann mittels der `login`-Funktion durchgeführt werden oder er
     *    wird automatisch bei Bedarf durchgeführt.
     * 3. Mit Übergabe eines Arrays: `new NamiWrapper(['username' => '123456',
     *    'password' => 'password1234', 'iniFileName' => 'custom.ini'])`. Wird noch ausführlicher
     *    dokumentiert!
     * @param mixed $config Der Benutzername, ein Array mit Parametern oder `null`.
     * @param string $password Das Passwort oder `null`.
     */
    public function __construct($config = null, $password = null) {
        // hard coded standard values;
        $this->config['iniFileName'] = 'nami.ini';
        $this->config['serverURL'] = 'https://nami.dpsg.de';
        $this->config['serverApiPath'] = '/ica/rest/';
        $this->config['serverCookieName'] = 'JSESSIONID';
        $this->config['serverLoginLoginValue'] = 'API';
        $this->config['APIVersionMajor'] = 1;
        $this->config['APIVersionMinor'] = 2;
        // check if given parameters are valid and throw exception if necessary
        if (!((null == $config && null == $password) || (is_string($config) && is_string($password)) || (is_array($config) && null == $password))) {
            if (is_string($password)) {
                $password = '*****';
            }
            if (is_array($config) && isset($config['password'])) {
                $config['password'] = '*****';
            }
            throw new InvalidArgumentException('The constructor of NamiWrapper was '
                    . 'provided with invalid parameters. Expected are either no '
                    . 'parameters, two strings or one array! Parameters that were '
                    . 'provided: \'' . var_export($config, true) . '\', \'' . 
                    var_export($password, true) . '\'.');
        }
        // in case $config is an array, check if custom ini-file is provided
        if (is_array($config) && is_string($config['iniFileName'])) {
            $this->config['iniFileName'] = $config['iniFileName'];
        }
        // load ini file and merge with $this->config
        $configIni = parse_ini_file($this->config['iniFileName'], false, INI_SCANNER_TYPED);
        if (is_array($configIni)) {
            $this->config = array_merge($this->config, $configIni);
        }
        // in case $config is an array, merge with $this->config
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        // in case username and password are directly provided, save them
        else {
            $this->config['username'] = $config;
            $this->config['password'] = $password;
        }
    }
    
    /**
     * Prüft einen übergebenen string darauf, ob es eine valide HTTP-Methoden-Bezeichung ist.
     * @param string $method String der geprüft werden soll.
     * @return boolean `true`, falls es sich um eine valide HTTP-Methoden-Bezeichung handelt, andernfalls `false`.
     */
    private static function _isValidMethod($method) {
        return (self::METHOD_POST == $method || self::METHOD_PUT == $method ||
                self::METHOD_GET == $method || self::METHOD_DELETE);
    }
    
    /**
     * Prüft einen übergebenen String darauf, ob es ein valider Codierungs-String ist (vgl. Klassenkonstanten).
     * @param string $encoding Der zu überprüfende string.
     * @return boolean `true` für valide Parameter, andernfalls `false`.
     */
    private static function _isValidEncoding($encoding) {
        return (self::ENCODING_JSON == $encoding || self::ENCODING_URL == $encoding ||
                self::ENCODING_JSON_MANUAL == $encoding || self::ENCODING_NO_CONTENT == $encoding);
    }
    
    /**
     * Gibt den Teil der URL zurück, der zwischen `ica/rest/` und bspw. `nami/` kommt.
     * @param mixed $arg1 Hauptversionsnummer (int) oder `login` (string) oder `null`.
     * @param mixed $arg2 Nebenversionsnummer (int) oder `null`, falls `$arg1` `login` oder `null` ist.
     * @return string Der Teil der URL, der zwischen `ica/rest/` und bspw. `nami/` kommt.
     */
    private function _apiVersion2UrlString($arg1, $arg2 = null) {
        if ('login' === $arg1 && null === $arg2) {
            return '';
        }
        if (null === $arg1 && null === $arg2) {
            $arg1 = $this->config['APIVersionMajor'];
            $arg2 = $this->config['APIVersionMinor'];
        }
        if (is_int($arg1) && is_int($arg1) && $arg1 >= 0 && $arg2 >= 0) {
            return 'api/' . $arg1 . '/' . $arg2 . '/service/';
        }
        throw new InvalidArgumentException('apiVersion2UrlString expects \'login\','
                . ' two non-negative integers or null as argument(s). Arguments given: \''
                . var_export($arg1, true) . '\', \'' . var_export($arg2, true) . '\'.');
    }
    
    /**
     * Gibt die Nami-Antwort auf die letzte Login-Anfrage zurück.
     * @return object Nami-Antwort auf die letzte Login-Anfrage. Falls bislang keine 
     * Login-Anfrage gestellt wurde, wird `null` zurück gegeben. Falls die Session
     * zwischenzeitlich abgemeldet wurde oder ausgelaufen ist, sind die Daten veraltet.
     */
    public function getLogin() {
        if (is_object($this->lastLoginResponse)) {
            return (clone $this->lastLoginResponse);
        }
        return null;
    }
    
    /**
     * Funktion zum Durchführen von Anfragen an die Nami.
     * @param string $method Die zu verwendende HTTP-Methode (siehe Klassenkonstanten).
     * @param string $resource Die betroffene Ressource (Teil der URL, der nach `service/`
     * kommt).
     * @param object $content null oder Objekt, abhängig von Methode und Ressource.
     * Wird JSON- bzw. URL-kodiert.
     * @param mixed $apiMajor Hauptversionsnummer der API (int) oder `login`, falls
     * eine Login-Abfrage geschickt werden soll. Optional, falls ein Standardwert gesetzt wurde.
     * @param mixed $apiMinor Nebenversionsnummer der API (int) oder `null`, falls
     * eine Login-Abfrage geschickt werden soll. Optional, falls ein Standardwert gesetzt wurde.
     * @param string $encoding Optional. NamiWrapper::ENCODING_JSON sorgt für eine
     * JSON-Kodierung (Standardwert für POST- und PUT-Anfragen), NamiWrapper::ENCODING_URL
     * sorg für URL-Kodierung (wird vmtl. ausschließlich bei Login-Anfragen erwartet),
     * NamiWrapper::ENCODING_JSON_MANUAL erwartet bereits JSON-kodierten Inhalt und
     * NamiWrapper::ENCODING_NO_CONTENT (Standard für GET- und DELETE-Anfragen) bedeutet,
     * dass kein Inhalt gesendet wird.
     */
    public function request($method, $resource, $content = null, $apiMajor = null, $apiMinor = null, $encoding = null) {
        // check if method is valid method
        if (!self::_isValidMethod($method)) {
            throw new InvalidArgumentException('request expects a valid HTTP method'
                    . ' string. Value provided: \'' . var_export($method, true) . '\'.');
        }
        // check if encoding is valid encoding and populate with default value
        if (null == $encoding) {
            switch ($method) {
                case self::METHOD_POST:
                    $encoding = self::ENCODING_JSON;
                    break;
                case self::METHOD_PUT:
                    $encoding = self::ENCODING_JSON;
                    break;
                case self::METHOD_GET:
                    $encoding = self::ENCODING_NO_CONTENT;
                    break;
                case self::METHOD_DELETE:
                    $encoding = self::ENCODING_NO_CONTENT;
                    break;
            }
        }
        if (!self::_isValidEncoding($encoding)) {
            throw new InvalidArgumentException('request excepts a valid encoding '
                    . 'string or null. Value provided: \'' . var_export($encoding, true) . '\'.');
        }
        // Add basic HTTP properties
        $httpsOpts = ['http' => [
                'method' => $method,
                'header' => 'Cookie: ' . $this->config['serverCookieName'] . '=' . $this->cookie . "\r\n"
            ],
        ];
        // add content if available
        switch($encoding) {
            case self::ENCODING_JSON:
                $contentEncoded = json_encode($content);
                $httpsOpts['http']['header'] .= 'Content-type: ' . self::MIME_JSON . "\r\n";
                break;
            case self::ENCODING_URL:
                $contentEncoded = http_build_query($content);
                $httpsOpts['http']['header'] .= 'Content-type: ' . self::MIME_URL_ENCODED . "\r\n";
                break;
            case self::ENCODING_JSON_MANUAL:
                $contentEncoded = $content;
                $httpsOpts['http']['header'] .= 'Content-type: ' . self::MIME_JSON . "\r\n";
                break;
        }
        if (self::ENCODING_NO_CONTENT != $encoding) {
            $httpsOpts['http']['content'] = $contentEncoded;
        }
        // create http-context
        $context = stream_context_create($httpsOpts);
        // build url
        try {
            $apiVersion = $this->_apiVersion2UrlString($apiMajor, $apiMinor);
        } catch(InvalidArgumentException $e) {
            throw new UnexpectedValueException('There is something wrong with the'
                    . ' API version numbers provided when calling method request,'
                    . ' provided constructing this NamiWrapper or stored in the used'
                    . ' ini file.', 0, $e);
        }
        $url = $this->config['serverURL'] . $this->config['serverApiPath'] . $apiVersion . $resource;
        // make HTTP request
        $response = file_get_contents($url, false, $context);
        return json_decode($response);
    }
    
    public function login($username = null, $password = null) {
        if (null == $username) {
            $username = $this->config['username'];
        }
        if (null == $password) {
            $password = $this->config['password'];
        }
        $content = ['Login' => $this->config['serverLoginLoginValue'], 'username' => $username, 'password' => $password];
        $response = $this->request(self::METHOD_POST, 'nami/auth/manual/sessionStartup', $content, 'login', null, self::ENCODING_URL);
        $this->cookie = $response->apiSessionToken;
        $this->lastLoginResponse = $response;
        return $response;
    }
}