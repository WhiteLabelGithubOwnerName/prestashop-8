<?php
/**
 * Secupay SDK
 *
 * This library allows to interact with the Secupay payment service.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


namespace Secupay\Sdk;

use Secupay\Sdk\ApiException;
use Secupay\Sdk\VersioningException;
use Secupay\Sdk\Http\HttpRequest;
use Secupay\Sdk\Http\HttpClientFactory;

/**
 * This class sends API calls to the endpoint.
 *
 * @category Class
 * @package  Secupay\Sdk
 * @author   Secupay AG.
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 */
final class ApiClient {

	/**
	 * The base path of the API endpoint.
	 *
	 * @var string
	 */
	private $basePath = 'https://app-wallee.com:443/api';

	/**
	 * An array of headers that are added to every request.
	 *
	 * @var array
	 */
	private $defaultHeaders = [
        'x-meta-sdk-version' => "4.6.0",
        'x-meta-sdk-language' => 'php',
        'x-meta-sdk-provider' => "Secupay",
    ];

	/**
	 * The user agent that is sent with any request.
	 *
	 * @var string
	 */
	private $userAgent = 'PHP-Client/4.6.0/php';

	/**
	 * The path to the certificate authority file.
	 *
	 * @var string
	 */
	private $certificateAuthority;

	/**
	 * Defines whether the certificate authority should be checked.
	 *
	 * @var boolean
	 */
	private $enableCertificateAuthorityCheck = true;

    /**
     * the constant for the default connection time out
     *
     * @var integer
     */
    const INITIAL_CONNECTION_TIMEOUT = 25;

    /**
	 * The connection timeout in seconds.
	 *
	 * @var integer
	 */
	private $connectionTimeout;

	/**
	 * The http client type to use for communication.
	 *
	 * @var string
	 */
	private $httpClientType = null;

	/**
	 * Defined whether debug information should be logged.
	 *
	 * @var boolean
	 */
	private $enableDebugging = false;

	/**
	 * The path to the debug file.
	 *
	 * @var string
	 */
	private $debugFile = 'php://output';

	/**
	 * The application user's id.
	 *
	 * @var integer
	 */
	private $userId;

	/**
	 * The application user's security key.
	 *
	 * @var string
	 */
	private $applicationKey;

	/**
	 * The object serializer.
	 *
	 * @var ObjectSerializer
	 */
	private $serializer;

	/**
	 * Constructor.
	 *
	 * @param integer $userId the application user's id
	 * @param string $applicationKey the application user's security key
	 */
	public function __construct($userId, $applicationKey) {
		if (empty($applicationKey)) {
			throw new \InvalidArgumentException('The application key cannot be empty or null.');
		}

		$this->userId = $userId;
        $this->applicationKey = $applicationKey;

        $this->connectionTimeout = self::INITIAL_CONNECTION_TIMEOUT;
		$this->certificateAuthority = dirname(__FILE__) . '/ca-bundle.crt';
		$this->serializer = new ObjectSerializer();
		$this->isDebuggingEnabled() ? $this->serializer->enableDebugging() : $this->serializer->disableDebugging();
		$this->serializer->setDebugFile($this->getDebugFile());
		$this->addDefaultHeader('x-meta-sdk-language-version', phpversion());
	}

	/**
	 * Returns the base path of the API endpoint.
	 *
	 * @return string
	 */
	public function getBasePath() {
		return $this->basePath;
	}

	/**
	 * Sets the base path of the API endpoint.
	 *
	 * @param string $basePath the base path
	 * @return ApiClient
	 */
	public function setBasePath($basePath) {
		$this->basePath = rtrim($basePath, '/');
		return $this;
	}

	/**
	 * Returns the path to the certificate authority file.
	 *
	 * @return string
	 */
	public function getCertificateAuthority() {
		return $this->certificateAuthority;
	}

	/**
	 * Sets the path to the certificate authority file. The certificate authority is used to verify the identity of the
	 * remote server. By setting this option the default certificate authority file will be overridden.
	 *
	 * To deactivate the check please use disableCertificateAuthorityCheck()
	 *
	 * @param string $certificateAuthorityFile the path to the certificate authority file
	 * @return ApiClient
	 */
	public function setCertificateAuthority($certificateAuthorityFile) {
		if (!file_exists($certificateAuthorityFile)) {
			throw new \InvalidArgumentException('The certificate authority file does not exist.');
		}

		$this->certificateAuthority = $certificateAuthorityFile;
		return $this;
	}

	/**
	 * Returns true, when the authority check is enabled. See enableCertificateAuthorityCheck() for more details about
	 * the authority check.
	 *
	 * @return boolean
	 */
	public function isCertificateAuthorityCheckEnabled() {
		return $this->enableCertificateAuthorityCheck;
	}

	/**
	 * Enables the check of the certificate authority. By checking the certificate authority the whole certificate
	 * chain is checked. the authority check prevents an attacker to use a man-in-the-middle attack.
	 *
	 * @return ApiClient
	 */
	public function enableCertificateAuthorityCheck() {
		$this->enableCertificateAuthorityCheck = true;
		return $this;
	}

	/**
	 * Disables the check of the certificate authority. See enableCertificateAuthorityCheck() for more details.
	 *
	 * @return ApiClient
	 */
	public function disableCertificateAuthorityCheck() {
		$this->enableCertificateAuthorityCheck = false;
		return $this;
	}

	/**
	 * Returns the connection timeout.
	 *
	 * @return int
	 */
	public function getConnectionTimeout() {
		return $this->connectionTimeout;
	}

	/**
	 * Sets the connection timeout in seconds.
	 *
	 * @param int $connectionTimeout the connection timeout in seconds
	 * @return ApiClient
	 */
	public function setConnectionTimeout($connectionTimeout) {
		if (!is_numeric($connectionTimeout) || $connectionTimeout < 0) {
			throw new \InvalidArgumentException('Timeout value must be numeric and a non-negative number.');
		}

		$this->connectionTimeout = $connectionTimeout;
		return $this;
	}

	/**
	 * Resets the connection timeout in seconds.
	 *
	 * @return ApiClient
	 */
	public function resetConnectionTimeout() {
		$this->connectionTimeout = self::INITIAL_CONNECTION_TIMEOUT;
		return $this;
	}

	/**
	 * Return the http client type to use for communication.
	 *
	 * @return string
	 * @see \Secupay\Sdk\Http\HttpClientFactory
	 */
	public function getHttpClientType() {
		return $this->httpClientType;
	}

	/**
	 * Set the http client type to use for communication.
	 * If this is null, all client are considered and the one working in the current environment is used.
	 *
	 * @param string $httpClientType the http client type
	 * @return ApiClient
	 * @see \Secupay\Sdk\Http\HttpClientFactory
	 */
	public function setHttpClientType($httpClientType) {
		$this->httpClientType = $httpClientType;
		return $this;
	}

	/**
	 * Returns the user agent header's value.
	 *
	 * @return string
	 */
	public function getUserAgent() {
		return $this->userAgent;
	}

	/**
	 * Sets the user agent header's value.
	 *
	 * @param string $userAgent the HTTP request's user agent
	 * @return ApiClient
	 */
	public function setUserAgent($userAgent) {
		if (!is_string($userAgent)) {
			throw new \InvalidArgumentException('User-agent must be a string.');
		}

		$this->userAgent = $userAgent;
		return $this;
	}

	/**
	 * Adds a default header.
	 *
	 * @param string $key the header's key
	 * @param string $value the header's value
	 * @return ApiClient
	 */
	public function addDefaultHeader($key, $value) {
		if (!is_string($key)) {
			throw new \InvalidArgumentException('The header key must be a string.');
		}

		$this->defaultHeaders[$key] = $value;
		return $this;
	}

	/**
     * Gets the default headers that will be sent in the request.
	 * 
	 * @since 3.1.2
	 * @return string[]
     */
    function getDefaultHeaders() {
        return $this->defaultHeaders;
    }

	/**
	 * Returns true, when debugging is enabled.
	 *
	 * @return boolean
	 */
	public function isDebuggingEnabled() {
		return $this->enableDebugging;
	}

	/**
	 * Enables debugging.
	 *
	 * @return ApiClient
	 */
	public function enableDebugging() {
		$this->enableDebugging = true;
		$this->serializer->enableDebugging();
		return $this;
	}

	/**
	 * Disables debugging.
	 *
	 * @return ApiClient
	 */
	public function disableDebugging() {
		$this->enableDebugging = false;
		$this->serializer->disableDebugging();
		return $this;
	}

	/**
	 * Returns the path to the debug file.
	 *
	 * @return string
	 */
	public function getDebugFile() {
		return $this->debugFile;
	}

	/**
	 * Sets the path to the debug file.
	 *
	 * @param string $debugFile the debug file
	 * @return ApiClient
	 */
	public function setDebugFile($debugFile) {
		$this->debugFile = $debugFile;
		$this->serializer->setDebugFile($debugFile);
		return $this;
	}

	/**
	 * Returns the serializer.
	 *
	 * @return ObjectSerializer
	 */
	public function getSerializer() {
		return $this->serializer;
	}

	/**
	 * Return the path of the temporary folder used to store downloaded files from endpoints with file response. By
	 * default the system's default temporary folder is used.
	 *
	 * @return string
	 */
	public function getTempFolderPath() {
		return $this->serializer->getTempFolderPath();
	}

	/**
	 * Sets the path to the temporary folder (for downloading files).
	 *
	 * @param string $tempFolderPath the temporary folder path
	 * @return ApiClient
	 */
	public function setTempFolderPath($tempFolderPath) {
		$this->serializer->setTempFolderPath($tempFolderPath);
		return $this;
	}

	/**
	 * Returns the 'Accept' header based on an array of accept values.
	 *
	 * @param string[] $accept the array of headers
	 * @return string
	 */
	public function selectHeaderAccept($accept) {
		if (empty($accept[0])) {
			return null;
		} elseif (preg_grep('/application\/json/i', $accept)) {
			return 'application/json';
		} else {
			return implode(',', $accept);
		}
	}

	/**
	 * Returns the 'Content Type' based on an array of content types.
	 *
	 * @param string[] $contentType the array of content types
	 * @return string
	 */
	public function selectHeaderContentType($contentType) {
		if (empty($contentType[0])) {
			return 'application/json';
		} elseif (preg_grep('/application\/json/i', $contentType)) {
			return 'application/json';
		} else {
			return implode(',', $contentType);
		}
	}

	/**
	 * Make the HTTP call (synchronously).
	 *
	 * @param string $resourcePath the path to the endpoint resource
	 * @param string $method	   the method to call
	 * @param array  $queryParams  the query parameters
	 * @param array  $postData	 the body parameters
	 * @param array  $headerParams the header parameters
	 * @param string $responseType the expected response type
	 * @param string $endpointPath the path to the method endpoint before expanding parameters
	 *
	 * @return \Secupay\Sdk\ApiResponse
	 * @throws \Secupay\Sdk\ApiException
	 * @throws \Secupay\Sdk\Http\ConnectionException
	 * @throws \Secupay\Sdk\VersioningException
	 */
	public function callApi($resourcePath, $method, $queryParams, $postData, $headerParams, $responseType = null, $endpointPath = null, $timeOut = null) {
        if ($timeOut === null) {
            $timeOut = $this->getConnectionTimeout();
        }
		$request = new HttpRequest($this->getSerializer(), $this->buildRequestUrl($resourcePath, $queryParams), $method, $this->generateUniqueToken(), $timeOut);
		$request->setUserAgent($this->getUserAgent());
		$request->addHeaders(array_merge(
			(array)$this->defaultHeaders,
			(array)$headerParams,
			(array)$this->getAuthenticationHeaders($request)
		));
		$request->setBody($postData);

		$response = HttpClientFactory::getClient($this->httpClientType)->send($this, $request);

		if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
			// return raw body if response is a file
			if (in_array($responseType, ['\SplFileObject', 'string'])) {
				return new ApiResponse($response->getStatusCode(), $response->getHeaders(), $response->getBody());
			}

			$data = json_decode($response->getBody());
			if (json_last_error() > 0) { // if response is a string
				$data = $response->getBody();
			}
		} else {
			if ($response->getStatusCode() == 409) {
				throw new VersioningException($resourcePath);
			}

			$data = json_decode($response->getBody());
			if (json_last_error() > 0) { // if response is a string
				$data = $response->getBody();
			}
            throw new ApiException(
                'Error ' . $response->getStatusCode() . ' connecting to the API (' . $request->getUrl() . ') : ' . $response->getBody(),
                $response->getStatusCode(),
                $response->getHeaders(),
                $data
            );
		}
		return new ApiResponse($response->getStatusCode(), $response->getHeaders(), $data);
	}

	/**
	 * Returns the request url.
	 *
	 * @param string $path the request path
	 * @param array $queryParams an array of query parameters
	 * @return string
	 */
	private function buildRequestUrl($path, $queryParams) {
		$url = $this->getBasePath() . $path;
		if (!empty($queryParams)) {
			$url = ($url . '?' . http_build_query($queryParams, '', '&'));
		}
		return $url;
	}

	/**
	 * Returns the headers used for authentication.
	 *
	 * @param HttpRequest $request
	 * @return array
	 */
	private function getAuthenticationHeaders(HttpRequest $request) {
		$timestamp = time();
		$version = 1;
		$path = $request->getPath();
		$securedData = implode('|', [$version, $this->userId, $timestamp, $request->getMethod(), $path]);

		$headers = [];
		$headers['x-mac-version'] = $version;
		$headers['x-mac-userid'] = $this->userId;
		$headers['x-mac-timestamp'] = $timestamp;
		$headers['x-mac-value'] = $this->calculateHmac($securedData);
		return $headers;
	}

	/**
	 * Calculates the hmac of the given data.
	 *
	 * @param string $securedData the data to calculate the hmac for
	 * @return string
	 */
	private function calculateHmac($securedData) {
		$decodedSecret = base64_decode($this->applicationKey);
		return base64_encode(hash_hmac('sha512', $securedData, $decodedSecret, true));
	}

	/**
	 * Generates a unique token to assign to the request.
	 *
	 * @return string
	 */
	private function generateUniqueToken() {
		$s = strtoupper(md5(uniqid(rand(),true)));
    	return substr($s,0,8) . '-' .
	        substr($s,8,4) . '-' .
	        substr($s,12,4). '-' .
	        substr($s,16,4). '-' .
	        substr($s,20);
	}

    // Builder pattern to get API instances for this client.
    
    protected $accountService;

    /**
     * @return \Secupay\Sdk\Service\AccountService
     */
    public function getAccountService() {
        if(is_null($this->accountService)){
            $this->accountService = new \Secupay\Sdk\Service\AccountService($this);
        }
        return $this->accountService;
    }
    
    protected $applicationUserService;

    /**
     * @return \Secupay\Sdk\Service\ApplicationUserService
     */
    public function getApplicationUserService() {
        if(is_null($this->applicationUserService)){
            $this->applicationUserService = new \Secupay\Sdk\Service\ApplicationUserService($this);
        }
        return $this->applicationUserService;
    }
    
    protected $chargeAttemptService;

    /**
     * @return \Secupay\Sdk\Service\ChargeAttemptService
     */
    public function getChargeAttemptService() {
        if(is_null($this->chargeAttemptService)){
            $this->chargeAttemptService = new \Secupay\Sdk\Service\ChargeAttemptService($this);
        }
        return $this->chargeAttemptService;
    }
    
    protected $chargeFlowLevelPaymentLinkService;

    /**
     * @return \Secupay\Sdk\Service\ChargeFlowLevelPaymentLinkService
     */
    public function getChargeFlowLevelPaymentLinkService() {
        if(is_null($this->chargeFlowLevelPaymentLinkService)){
            $this->chargeFlowLevelPaymentLinkService = new \Secupay\Sdk\Service\ChargeFlowLevelPaymentLinkService($this);
        }
        return $this->chargeFlowLevelPaymentLinkService;
    }
    
    protected $chargeFlowLevelService;

    /**
     * @return \Secupay\Sdk\Service\ChargeFlowLevelService
     */
    public function getChargeFlowLevelService() {
        if(is_null($this->chargeFlowLevelService)){
            $this->chargeFlowLevelService = new \Secupay\Sdk\Service\ChargeFlowLevelService($this);
        }
        return $this->chargeFlowLevelService;
    }
    
    protected $chargeFlowService;

    /**
     * @return \Secupay\Sdk\Service\ChargeFlowService
     */
    public function getChargeFlowService() {
        if(is_null($this->chargeFlowService)){
            $this->chargeFlowService = new \Secupay\Sdk\Service\ChargeFlowService($this);
        }
        return $this->chargeFlowService;
    }
    
    protected $conditionTypeService;

    /**
     * @return \Secupay\Sdk\Service\ConditionTypeService
     */
    public function getConditionTypeService() {
        if(is_null($this->conditionTypeService)){
            $this->conditionTypeService = new \Secupay\Sdk\Service\ConditionTypeService($this);
        }
        return $this->conditionTypeService;
    }
    
    protected $countryService;

    /**
     * @return \Secupay\Sdk\Service\CountryService
     */
    public function getCountryService() {
        if(is_null($this->countryService)){
            $this->countryService = new \Secupay\Sdk\Service\CountryService($this);
        }
        return $this->countryService;
    }
    
    protected $countryStateService;

    /**
     * @return \Secupay\Sdk\Service\CountryStateService
     */
    public function getCountryStateService() {
        if(is_null($this->countryStateService)){
            $this->countryStateService = new \Secupay\Sdk\Service\CountryStateService($this);
        }
        return $this->countryStateService;
    }
    
    protected $currencyService;

    /**
     * @return \Secupay\Sdk\Service\CurrencyService
     */
    public function getCurrencyService() {
        if(is_null($this->currencyService)){
            $this->currencyService = new \Secupay\Sdk\Service\CurrencyService($this);
        }
        return $this->currencyService;
    }
    
    protected $customerAddressService;

    /**
     * @return \Secupay\Sdk\Service\CustomerAddressService
     */
    public function getCustomerAddressService() {
        if(is_null($this->customerAddressService)){
            $this->customerAddressService = new \Secupay\Sdk\Service\CustomerAddressService($this);
        }
        return $this->customerAddressService;
    }
    
    protected $customerCommentService;

    /**
     * @return \Secupay\Sdk\Service\CustomerCommentService
     */
    public function getCustomerCommentService() {
        if(is_null($this->customerCommentService)){
            $this->customerCommentService = new \Secupay\Sdk\Service\CustomerCommentService($this);
        }
        return $this->customerCommentService;
    }
    
    protected $customerService;

    /**
     * @return \Secupay\Sdk\Service\CustomerService
     */
    public function getCustomerService() {
        if(is_null($this->customerService)){
            $this->customerService = new \Secupay\Sdk\Service\CustomerService($this);
        }
        return $this->customerService;
    }
    
    protected $deliveryIndicationService;

    /**
     * @return \Secupay\Sdk\Service\DeliveryIndicationService
     */
    public function getDeliveryIndicationService() {
        if(is_null($this->deliveryIndicationService)){
            $this->deliveryIndicationService = new \Secupay\Sdk\Service\DeliveryIndicationService($this);
        }
        return $this->deliveryIndicationService;
    }
    
    protected $humanUserService;

    /**
     * @return \Secupay\Sdk\Service\HumanUserService
     */
    public function getHumanUserService() {
        if(is_null($this->humanUserService)){
            $this->humanUserService = new \Secupay\Sdk\Service\HumanUserService($this);
        }
        return $this->humanUserService;
    }
    
    protected $labelDescriptionGroupService;

    /**
     * @return \Secupay\Sdk\Service\LabelDescriptionGroupService
     */
    public function getLabelDescriptionGroupService() {
        if(is_null($this->labelDescriptionGroupService)){
            $this->labelDescriptionGroupService = new \Secupay\Sdk\Service\LabelDescriptionGroupService($this);
        }
        return $this->labelDescriptionGroupService;
    }
    
    protected $labelDescriptionService;

    /**
     * @return \Secupay\Sdk\Service\LabelDescriptionService
     */
    public function getLabelDescriptionService() {
        if(is_null($this->labelDescriptionService)){
            $this->labelDescriptionService = new \Secupay\Sdk\Service\LabelDescriptionService($this);
        }
        return $this->labelDescriptionService;
    }
    
    protected $languageService;

    /**
     * @return \Secupay\Sdk\Service\LanguageService
     */
    public function getLanguageService() {
        if(is_null($this->languageService)){
            $this->languageService = new \Secupay\Sdk\Service\LanguageService($this);
        }
        return $this->languageService;
    }
    
    protected $legalOrganizationFormService;

    /**
     * @return \Secupay\Sdk\Service\LegalOrganizationFormService
     */
    public function getLegalOrganizationFormService() {
        if(is_null($this->legalOrganizationFormService)){
            $this->legalOrganizationFormService = new \Secupay\Sdk\Service\LegalOrganizationFormService($this);
        }
        return $this->legalOrganizationFormService;
    }
    
    protected $manualTaskService;

    /**
     * @return \Secupay\Sdk\Service\ManualTaskService
     */
    public function getManualTaskService() {
        if(is_null($this->manualTaskService)){
            $this->manualTaskService = new \Secupay\Sdk\Service\ManualTaskService($this);
        }
        return $this->manualTaskService;
    }
    
    protected $paymentConnectorConfigurationService;

    /**
     * @return \Secupay\Sdk\Service\PaymentConnectorConfigurationService
     */
    public function getPaymentConnectorConfigurationService() {
        if(is_null($this->paymentConnectorConfigurationService)){
            $this->paymentConnectorConfigurationService = new \Secupay\Sdk\Service\PaymentConnectorConfigurationService($this);
        }
        return $this->paymentConnectorConfigurationService;
    }
    
    protected $paymentConnectorService;

    /**
     * @return \Secupay\Sdk\Service\PaymentConnectorService
     */
    public function getPaymentConnectorService() {
        if(is_null($this->paymentConnectorService)){
            $this->paymentConnectorService = new \Secupay\Sdk\Service\PaymentConnectorService($this);
        }
        return $this->paymentConnectorService;
    }
    
    protected $paymentMethodBrandService;

    /**
     * @return \Secupay\Sdk\Service\PaymentMethodBrandService
     */
    public function getPaymentMethodBrandService() {
        if(is_null($this->paymentMethodBrandService)){
            $this->paymentMethodBrandService = new \Secupay\Sdk\Service\PaymentMethodBrandService($this);
        }
        return $this->paymentMethodBrandService;
    }
    
    protected $paymentMethodConfigurationService;

    /**
     * @return \Secupay\Sdk\Service\PaymentMethodConfigurationService
     */
    public function getPaymentMethodConfigurationService() {
        if(is_null($this->paymentMethodConfigurationService)){
            $this->paymentMethodConfigurationService = new \Secupay\Sdk\Service\PaymentMethodConfigurationService($this);
        }
        return $this->paymentMethodConfigurationService;
    }
    
    protected $paymentMethodService;

    /**
     * @return \Secupay\Sdk\Service\PaymentMethodService
     */
    public function getPaymentMethodService() {
        if(is_null($this->paymentMethodService)){
            $this->paymentMethodService = new \Secupay\Sdk\Service\PaymentMethodService($this);
        }
        return $this->paymentMethodService;
    }
    
    protected $paymentProcessorConfigurationService;

    /**
     * @return \Secupay\Sdk\Service\PaymentProcessorConfigurationService
     */
    public function getPaymentProcessorConfigurationService() {
        if(is_null($this->paymentProcessorConfigurationService)){
            $this->paymentProcessorConfigurationService = new \Secupay\Sdk\Service\PaymentProcessorConfigurationService($this);
        }
        return $this->paymentProcessorConfigurationService;
    }
    
    protected $paymentProcessorService;

    /**
     * @return \Secupay\Sdk\Service\PaymentProcessorService
     */
    public function getPaymentProcessorService() {
        if(is_null($this->paymentProcessorService)){
            $this->paymentProcessorService = new \Secupay\Sdk\Service\PaymentProcessorService($this);
        }
        return $this->paymentProcessorService;
    }
    
    protected $permissionService;

    /**
     * @return \Secupay\Sdk\Service\PermissionService
     */
    public function getPermissionService() {
        if(is_null($this->permissionService)){
            $this->permissionService = new \Secupay\Sdk\Service\PermissionService($this);
        }
        return $this->permissionService;
    }
    
    protected $refundCommentService;

    /**
     * @return \Secupay\Sdk\Service\RefundCommentService
     */
    public function getRefundCommentService() {
        if(is_null($this->refundCommentService)){
            $this->refundCommentService = new \Secupay\Sdk\Service\RefundCommentService($this);
        }
        return $this->refundCommentService;
    }
    
    protected $refundService;

    /**
     * @return \Secupay\Sdk\Service\RefundService
     */
    public function getRefundService() {
        if(is_null($this->refundService)){
            $this->refundService = new \Secupay\Sdk\Service\RefundService($this);
        }
        return $this->refundService;
    }
    
    protected $shopifyRecurringOrderService;

    /**
     * @return \Secupay\Sdk\Service\ShopifyRecurringOrderService
     */
    public function getShopifyRecurringOrderService() {
        if(is_null($this->shopifyRecurringOrderService)){
            $this->shopifyRecurringOrderService = new \Secupay\Sdk\Service\ShopifyRecurringOrderService($this);
        }
        return $this->shopifyRecurringOrderService;
    }
    
    protected $shopifySubscriberService;

    /**
     * @return \Secupay\Sdk\Service\ShopifySubscriberService
     */
    public function getShopifySubscriberService() {
        if(is_null($this->shopifySubscriberService)){
            $this->shopifySubscriberService = new \Secupay\Sdk\Service\ShopifySubscriberService($this);
        }
        return $this->shopifySubscriberService;
    }
    
    protected $shopifySubscriptionProductService;

    /**
     * @return \Secupay\Sdk\Service\ShopifySubscriptionProductService
     */
    public function getShopifySubscriptionProductService() {
        if(is_null($this->shopifySubscriptionProductService)){
            $this->shopifySubscriptionProductService = new \Secupay\Sdk\Service\ShopifySubscriptionProductService($this);
        }
        return $this->shopifySubscriptionProductService;
    }
    
    protected $shopifySubscriptionService;

    /**
     * @return \Secupay\Sdk\Service\ShopifySubscriptionService
     */
    public function getShopifySubscriptionService() {
        if(is_null($this->shopifySubscriptionService)){
            $this->shopifySubscriptionService = new \Secupay\Sdk\Service\ShopifySubscriptionService($this);
        }
        return $this->shopifySubscriptionService;
    }
    
    protected $shopifySubscriptionSuspensionService;

    /**
     * @return \Secupay\Sdk\Service\ShopifySubscriptionSuspensionService
     */
    public function getShopifySubscriptionSuspensionService() {
        if(is_null($this->shopifySubscriptionSuspensionService)){
            $this->shopifySubscriptionSuspensionService = new \Secupay\Sdk\Service\ShopifySubscriptionSuspensionService($this);
        }
        return $this->shopifySubscriptionSuspensionService;
    }
    
    protected $shopifySubscriptionVersionService;

    /**
     * @return \Secupay\Sdk\Service\ShopifySubscriptionVersionService
     */
    public function getShopifySubscriptionVersionService() {
        if(is_null($this->shopifySubscriptionVersionService)){
            $this->shopifySubscriptionVersionService = new \Secupay\Sdk\Service\ShopifySubscriptionVersionService($this);
        }
        return $this->shopifySubscriptionVersionService;
    }
    
    protected $shopifyTransactionService;

    /**
     * @return \Secupay\Sdk\Service\ShopifyTransactionService
     */
    public function getShopifyTransactionService() {
        if(is_null($this->shopifyTransactionService)){
            $this->shopifyTransactionService = new \Secupay\Sdk\Service\ShopifyTransactionService($this);
        }
        return $this->shopifyTransactionService;
    }
    
    protected $spaceService;

    /**
     * @return \Secupay\Sdk\Service\SpaceService
     */
    public function getSpaceService() {
        if(is_null($this->spaceService)){
            $this->spaceService = new \Secupay\Sdk\Service\SpaceService($this);
        }
        return $this->spaceService;
    }
    
    protected $staticValueService;

    /**
     * @return \Secupay\Sdk\Service\StaticValueService
     */
    public function getStaticValueService() {
        if(is_null($this->staticValueService)){
            $this->staticValueService = new \Secupay\Sdk\Service\StaticValueService($this);
        }
        return $this->staticValueService;
    }
    
    protected $tokenService;

    /**
     * @return \Secupay\Sdk\Service\TokenService
     */
    public function getTokenService() {
        if(is_null($this->tokenService)){
            $this->tokenService = new \Secupay\Sdk\Service\TokenService($this);
        }
        return $this->tokenService;
    }
    
    protected $tokenVersionService;

    /**
     * @return \Secupay\Sdk\Service\TokenVersionService
     */
    public function getTokenVersionService() {
        if(is_null($this->tokenVersionService)){
            $this->tokenVersionService = new \Secupay\Sdk\Service\TokenVersionService($this);
        }
        return $this->tokenVersionService;
    }
    
    protected $transactionCommentService;

    /**
     * @return \Secupay\Sdk\Service\TransactionCommentService
     */
    public function getTransactionCommentService() {
        if(is_null($this->transactionCommentService)){
            $this->transactionCommentService = new \Secupay\Sdk\Service\TransactionCommentService($this);
        }
        return $this->transactionCommentService;
    }
    
    protected $transactionIframeService;

    /**
     * @return \Secupay\Sdk\Service\TransactionIframeService
     */
    public function getTransactionIframeService() {
        if(is_null($this->transactionIframeService)){
            $this->transactionIframeService = new \Secupay\Sdk\Service\TransactionIframeService($this);
        }
        return $this->transactionIframeService;
    }
    
    protected $transactionInvoiceCommentService;

    /**
     * @return \Secupay\Sdk\Service\TransactionInvoiceCommentService
     */
    public function getTransactionInvoiceCommentService() {
        if(is_null($this->transactionInvoiceCommentService)){
            $this->transactionInvoiceCommentService = new \Secupay\Sdk\Service\TransactionInvoiceCommentService($this);
        }
        return $this->transactionInvoiceCommentService;
    }
    
    protected $transactionInvoiceService;

    /**
     * @return \Secupay\Sdk\Service\TransactionInvoiceService
     */
    public function getTransactionInvoiceService() {
        if(is_null($this->transactionInvoiceService)){
            $this->transactionInvoiceService = new \Secupay\Sdk\Service\TransactionInvoiceService($this);
        }
        return $this->transactionInvoiceService;
    }
    
    protected $transactionLightboxService;

    /**
     * @return \Secupay\Sdk\Service\TransactionLightboxService
     */
    public function getTransactionLightboxService() {
        if(is_null($this->transactionLightboxService)){
            $this->transactionLightboxService = new \Secupay\Sdk\Service\TransactionLightboxService($this);
        }
        return $this->transactionLightboxService;
    }
    
    protected $transactionLineItemVersionService;

    /**
     * @return \Secupay\Sdk\Service\TransactionLineItemVersionService
     */
    public function getTransactionLineItemVersionService() {
        if(is_null($this->transactionLineItemVersionService)){
            $this->transactionLineItemVersionService = new \Secupay\Sdk\Service\TransactionLineItemVersionService($this);
        }
        return $this->transactionLineItemVersionService;
    }
    
    protected $transactionPaymentPageService;

    /**
     * @return \Secupay\Sdk\Service\TransactionPaymentPageService
     */
    public function getTransactionPaymentPageService() {
        if(is_null($this->transactionPaymentPageService)){
            $this->transactionPaymentPageService = new \Secupay\Sdk\Service\TransactionPaymentPageService($this);
        }
        return $this->transactionPaymentPageService;
    }
    
    protected $transactionService;

    /**
     * @return \Secupay\Sdk\Service\TransactionService
     */
    public function getTransactionService() {
        if(is_null($this->transactionService)){
            $this->transactionService = new \Secupay\Sdk\Service\TransactionService($this);
        }
        return $this->transactionService;
    }
    
    protected $userAccountRoleService;

    /**
     * @return \Secupay\Sdk\Service\UserAccountRoleService
     */
    public function getUserAccountRoleService() {
        if(is_null($this->userAccountRoleService)){
            $this->userAccountRoleService = new \Secupay\Sdk\Service\UserAccountRoleService($this);
        }
        return $this->userAccountRoleService;
    }
    
    protected $userSpaceRoleService;

    /**
     * @return \Secupay\Sdk\Service\UserSpaceRoleService
     */
    public function getUserSpaceRoleService() {
        if(is_null($this->userSpaceRoleService)){
            $this->userSpaceRoleService = new \Secupay\Sdk\Service\UserSpaceRoleService($this);
        }
        return $this->userSpaceRoleService;
    }
    
    protected $webhookEncryptionService;

    /**
     * @return \Secupay\Sdk\Service\WebhookEncryptionService
     */
    public function getWebhookEncryptionService() {
        if(is_null($this->webhookEncryptionService)){
            $this->webhookEncryptionService = new \Secupay\Sdk\Service\WebhookEncryptionService($this);
        }
        return $this->webhookEncryptionService;
    }
    
    protected $webhookListenerService;

    /**
     * @return \Secupay\Sdk\Service\WebhookListenerService
     */
    public function getWebhookListenerService() {
        if(is_null($this->webhookListenerService)){
            $this->webhookListenerService = new \Secupay\Sdk\Service\WebhookListenerService($this);
        }
        return $this->webhookListenerService;
    }
    
    protected $webhookUrlService;

    /**
     * @return \Secupay\Sdk\Service\WebhookUrlService
     */
    public function getWebhookUrlService() {
        if(is_null($this->webhookUrlService)){
            $this->webhookUrlService = new \Secupay\Sdk\Service\WebhookUrlService($this);
        }
        return $this->webhookUrlService;
    }
    

}
