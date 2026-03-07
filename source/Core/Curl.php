<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * CURL request handler.
 * Handles CURL calls
 */
class Curl
{
    /** Curl option for setting the timeout of whole execution process. */
    public const EXECUTION_TIMEOUT_OPTION = 'CURLOPT_TIMEOUT';

    /** Curl option for setting the timeout for connect. */
    public const CONNECT_TIMEOUT_OPTION = 'CURLOPT_CONNECTTIMEOUT';

    /**
     * Curl instance.
     *
     * @var resource
     */
    protected $_rCurl;

    /**
     * URL to call
     *
     * @var string|null
     */
    protected $_sUrl;

    /**
     * Query like "param1=value1&param2=values2.."
     *
     * @return string
     */
    protected $_sQuery;

    /**
     * Set CURL method
     *
     * @return string
     */
    protected $_sMethod = 'POST';

    /**
     * Parameter to be added to call url
     *
     * @var array|null
     */
    protected $_aParameters;

    /**
     * Connection Charset.
     *
     * @var string
     */
    protected $_sConnectionCharset = 'UTF-8';

    /**
     * Curl call header.
     *
     * @var array
     */
    protected $_aHeader;

    /**
     * Host for header.
     *
     * @var string
     */
    protected $_sHost;

    /**
     * Curl Options
     *
     * @var array
     */
    protected $_aOptions = ['CURLOPT_RETURNTRANSFER' => 1];

    /**
     * Request HTTP status call code.
     *
     * @var int|null
     */
    protected $_sStatusCode;

    /**
     * Sets url to call
     *
     * @param string $url URL to call.
     */
    public function setUrl($url): void
    {
        $this->_sUrl = $url;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->getMethod() == 'GET' && $this->getQuery()) {
            $this->_sUrl = $this->_sUrl . '?' . $this->getQuery();
        }

        return $this->_sUrl;
    }

    /**
     * Set query like "param1=value1&param2=values2.."
     *
     * @param string $query Request query.
     */
    public function setQuery($query): void
    {
        $this->_sQuery = $query;
    }

    /**
     * Builds query like "param1=value1&param2=values2.."
     *
     * @return string
     */
    public function getQuery()
    {
        if (is_null($this->_sQuery)) {
            $query = '';
            if ($params = $this->getParameters()) {
                $params = $this->prepareQueryParameters($params);
                $query = http_build_query($params, '', '&');
            }
            $this->setQuery($query);
        }

        return $this->_sQuery;
    }

    /**
     * Sets parameters to be added to call url.
     *
     * @param array $parameters parameters
     */
    public function setParameters($parameters): void
    {
        $this->setQuery(null);
        $this->_aParameters = $parameters;
    }

    /**
     * Return parameters to be added to call url.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->_aParameters;
    }

    /**
     * Sets host.
     *
     * @param string $host
     */
    public function setHost($host): void
    {
        $this->_sHost = $host;
    }

    /**
     * Returns host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_sHost;
    }

    /**
     * Set header.
     *
     * @param array $header
     */
    public function setHeader($header = null): void
    {
        if (is_null($header) && $this->getMethod() == 'POST') {
            $host = $this->getHost();

            $header = [];
            $header[] = 'POST /cgi-bin/webscr HTTP/1.1';
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            if (isset($host)) {
                $header[] = 'Host: ' . $host;
            }
            $header[] = 'Connection: close';
        }
        $this->_aHeader = $header;
    }

    /**
     * Forms header from host.
     *
     * @return array
     */
    public function getHeader()
    {
        if (is_null($this->_aHeader)) {
            $this->setHeader();
        }

        return $this->_aHeader;
    }

    /**
     * Set method to send (POST/GET)
     *
     * @param string $method method to send (POST/GET)
     */
    public function setMethod($method): void
    {
        $this->_sMethod = strtoupper($method);
    }

    /**
     * Return method to send
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_sMethod;
    }

    /**
     * Sets an option for a cURL transfer
     *
     * @param string $name  curl option name to set value to.
     * @param string $value curl option value to set.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException curl errors
     */
    public function setOption($name, $value): void
    {
        if (!str_starts_with($name, 'CURLOPT_') || !defined($constant  = strtoupper($name))) {
            $exception = oxNew(\OxidEsales\Eshop\Core\Exception\StandardException::class);
            $lang = \OxidEsales\Eshop\Core\Registry::getLang();
            $exception->setMessage(sprintf($lang->translateString('EXCEPTION_NOT_VALID_CURL_CONSTANT', $lang->getTplLanguage()), $name));
            throw $exception;
        }

        $this->_aOptions[$name] = $value;
    }

    /**
     * Gets all options for a cURL transfer
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_aOptions;
    }

    /**
     * Executes curl call and returns response data as associative array.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException on curl errors
     */
    public function execute(): string
    {
        $this->setOptions();

        $response = $this->executeCurl();
        $this->saveStatusCode();

        $curlErrorNumber = $this->getErrorNumber();

        $this->close();

        if ($curlErrorNumber) {
            $exception = oxNew(\OxidEsales\Eshop\Core\Exception\StandardException::class);
            $lang = \OxidEsales\Eshop\Core\Registry::getLang();
            $exception->setMessage(sprintf($lang->translateString('EXCEPTION_CURL_ERROR', $lang->getTplLanguage()), $curlErrorNumber));
            throw $exception;
        }

        return $response;
    }

    /**
     * Set connection charset
     *
     * @param string $charset charset
     */
    public function setConnectionCharset($charset): void
    {
        $this->_sConnectionCharset = $charset;
    }

    /**
     * Return connection charset
     *
     * @return string
     */
    public function getConnectionCharset()
    {
        return $this->_sConnectionCharset;
    }

    /**
     * Return HTTP status code.
     *
     * @return int HTTP status code.
     */
    public function getStatusCode()
    {
        return $this->_sStatusCode;
    }

    /**
     * Sets resource
     *
     * @param resource $rCurl curl.
     */
    protected function setResource($rCurl)
    {
        $this->_rCurl = $rCurl;
    }

    /**
     * Returns curl resource
     *
     * @return resource
     */
    protected function getResource()
    {
        if (is_null($this->_rCurl)) {
            $this->setResource(curl_init());
        }

        return $this->_rCurl;
    }

    /**
     * Set Curl Options
     */
    protected function setOptions()
    {
        if (!is_null($this->getHeader())) {
            $this->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
        }
        $this->setOpt(CURLOPT_URL, $this->getUrl());

        if ($this->getMethod() == 'POST') {
            $this->setOpt(CURLOPT_POST, 1);
            $this->setOpt(CURLOPT_POSTFIELDS, $this->getQuery());
        }

        $options = $this->getOptions();
        if (count($options)) {
            foreach ($options as $name => $mValue) {
                $this->setOpt(constant($name), $mValue);
            }
        }
    }

    /**
     * Wrapper function to be mocked for testing.
     */
    protected function executeCurl(): string
    {
        return curl_exec($this->getResource());
    }

    /**
     * Wrapper function to be mocked for testing.
     */
    protected function close()
    {
        $this->setResource(null);
    }

    /**
     * Wrapper function to be mocked for testing.
     *
     * @param string $name  curl option name to set value to.
     * @param string $value curl option value to set.
     */
    protected function setOpt($name, $value)
    {
        curl_setopt($this->getResource(), $name, $value);
    }

    /**
     * Check if curl has errors. Set error message if has.
     */
    protected function getErrorNumber(): int
    {
        return curl_errno($this->getResource());
    }

    /**
     * Sets current request HTTP status code.
     */
    protected function saveStatusCode()
    {
        $this->_sStatusCode = curl_getinfo($this->getResource(), CURLINFO_HTTP_CODE);
    }

    /**
     * Decodes html entities.
     *
     * @param array $params Parameters.
     */
    protected function prepareQueryParameters($params): array
    {
        return array_map($this->htmlDecode(...), array_filter($params));
    }

    /**
     * Decode (if needed) html entity.
     *
     * @param mixed $mParam query
     *
     * @return string
     */
    protected function htmlDecode($mParam)
    {
        if (is_array($mParam)) {
            return $this->prepareQueryParameters($mParam);
        }

        return html_entity_decode(stripslashes((string) $mParam), ENT_QUOTES, $this->getConnectionCharset());
    }
}
