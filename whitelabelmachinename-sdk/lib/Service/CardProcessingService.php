<?php
/**
 * WhiteLabelName SDK
 *
 * This library allows to interact with the WhiteLabelName payment service.
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


namespace WhiteLabelMachineName\Sdk\Service;

use WhiteLabelMachineName\Sdk\ApiClient;
use WhiteLabelMachineName\Sdk\ApiException;
use WhiteLabelMachineName\Sdk\ApiResponse;
use WhiteLabelMachineName\Sdk\Http\HttpRequest;
use WhiteLabelMachineName\Sdk\ObjectSerializer;

/**
 * CardProcessingService service
 *
 * @category Class
 * @package  WhiteLabelMachineName\Sdk
 * @author   WhiteLabelMachineName
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 */
class CardProcessingService {

	/**
	 * The API client instance.
	 *
	 * @var ApiClient
	 */
	private $apiClient;

	/**
	 * Constructor.
	 *
	 * @param ApiClient $apiClient the api client
	 */
	public function __construct(ApiClient $apiClient) {
		if (is_null($apiClient)) {
			throw new \InvalidArgumentException('The api client is required.');
		}

		$this->apiClient = $apiClient;
	}

	/**
	 * Returns the API client instance.
	 *
	 * @return ApiClient
	 */
	public function getApiClient() {
		return $this->apiClient;
	}


	/**
	 * Operation process
	 *
	 * Process
	 *
	 * @param int $space_id  (required)
	 * @param int $transaction_id The ID of the transaction which should be processed. (required)
	 * @param int $payment_method_configuration_id The payment method configuration ID which is applied to the transaction. (required)
	 * @param \WhiteLabelMachineName\Sdk\Model\AuthenticatedCardDataCreate $card_data The card details as JSON in plain which should be used to authorize the payment. (required)
	 * @throws \WhiteLabelMachineName\Sdk\ApiException
	 * @throws \WhiteLabelMachineName\Sdk\VersioningException
	 * @throws \WhiteLabelMachineName\Sdk\Http\ConnectionException
	 * @return \WhiteLabelMachineName\Sdk\Model\Transaction
	 */
	public function process($space_id, $transaction_id, $payment_method_configuration_id, $card_data) {
		return $this->processWithHttpInfo($space_id, $transaction_id, $payment_method_configuration_id, $card_data)->getData();
	}

	/**
	 * Operation processWithHttpInfo
	 *
	 * Process
     
     *
	 * @param int $space_id  (required)
	 * @param int $transaction_id The ID of the transaction which should be processed. (required)
	 * @param int $payment_method_configuration_id The payment method configuration ID which is applied to the transaction. (required)
	 * @param \WhiteLabelMachineName\Sdk\Model\AuthenticatedCardDataCreate $card_data The card details as JSON in plain which should be used to authorize the payment. (required)
	 * @throws \WhiteLabelMachineName\Sdk\ApiException
	 * @throws \WhiteLabelMachineName\Sdk\VersioningException
	 * @throws \WhiteLabelMachineName\Sdk\Http\ConnectionException
	 * @return ApiResponse
	 */
	public function processWithHttpInfo($space_id, $transaction_id, $payment_method_configuration_id, $card_data) {
		// verify the required parameter 'space_id' is set
		if (is_null($space_id)) {
			throw new \InvalidArgumentException('Missing the required parameter $space_id when calling process');
		}
		// verify the required parameter 'transaction_id' is set
		if (is_null($transaction_id)) {
			throw new \InvalidArgumentException('Missing the required parameter $transaction_id when calling process');
		}
		// verify the required parameter 'payment_method_configuration_id' is set
		if (is_null($payment_method_configuration_id)) {
			throw new \InvalidArgumentException('Missing the required parameter $payment_method_configuration_id when calling process');
		}
		// verify the required parameter 'card_data' is set
		if (is_null($card_data)) {
			throw new \InvalidArgumentException('Missing the required parameter $card_data when calling process');
		}
		// header params
		$headerParams = [];
		$headerAccept = $this->apiClient->selectHeaderAccept(['application/json;charset=utf-8']);
		if (!is_null($headerAccept)) {
			$headerParams[HttpRequest::HEADER_KEY_ACCEPT] = $headerAccept;
		}
		$headerParams[HttpRequest::HEADER_KEY_CONTENT_TYPE] = $this->apiClient->selectHeaderContentType(['application/json;charset=utf-8']);

		// query params
		$queryParams = [];
		if (!is_null($space_id)) {
			$queryParams['spaceId'] = $this->apiClient->getSerializer()->toQueryValue($space_id);
		}
		if (!is_null($transaction_id)) {
			$queryParams['transactionId'] = $this->apiClient->getSerializer()->toQueryValue($transaction_id);
		}
		if (!is_null($payment_method_configuration_id)) {
			$queryParams['paymentMethodConfigurationId'] = $this->apiClient->getSerializer()->toQueryValue($payment_method_configuration_id);
		}

		// path params
		$resourcePath = '/card-processing/process';
		// default format to json
		$resourcePath = str_replace('{format}', 'json', $resourcePath);

		// form params
		$formParams = [];
		// body params
		$tempBody = null;
		if (isset($card_data)) {
			$tempBody = $card_data;
		}

		// for model (json/xml)
		$httpBody = '';
		if (isset($tempBody)) {
			$httpBody = $tempBody; // $tempBody is the method argument, if present
		} elseif (!empty($formParams)) {
			$httpBody = $formParams; // for HTTP post (form)
		}
		// make the API Call
		try {
			$response = $this->apiClient->callApi(
				$resourcePath,
				'POST',
				$queryParams,
				$httpBody,
				$headerParams,
				'\WhiteLabelMachineName\Sdk\Model\Transaction',
				'/card-processing/process'
            );
			return new ApiResponse($response->getStatusCode(), $response->getHeaders(), $this->apiClient->getSerializer()->deserialize($response->getData(), '\WhiteLabelMachineName\Sdk\Model\Transaction', $response->getHeaders()));
		} catch (ApiException $e) {
			switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\Transaction',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\ClientError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
                case 442:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\ClientError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
                case 542:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\ServerError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
			}
			throw $e;
		}
	}

	/**
	 * Operation processWith3DSecure
	 *
	 * Process With 3-D Secure
	 *
	 * @param int $space_id  (required)
	 * @param int $transaction_id The ID of the transaction which should be processed. (required)
	 * @param int $payment_method_configuration_id The payment method configuration ID which is applied to the transaction. (required)
	 * @param \WhiteLabelMachineName\Sdk\Model\TokenizedCardDataCreate $card_data The card details as JSON in plain which should be used to authorize the payment. (required)
	 * @throws \WhiteLabelMachineName\Sdk\ApiException
	 * @throws \WhiteLabelMachineName\Sdk\VersioningException
	 * @throws \WhiteLabelMachineName\Sdk\Http\ConnectionException
	 * @return string
	 */
	public function processWith3DSecure($space_id, $transaction_id, $payment_method_configuration_id, $card_data) {
		return $this->processWith3DSecureWithHttpInfo($space_id, $transaction_id, $payment_method_configuration_id, $card_data)->getData();
	}

	/**
	 * Operation processWith3DSecureWithHttpInfo
	 *
	 * Process With 3-D Secure
     
     *
	 * @param int $space_id  (required)
	 * @param int $transaction_id The ID of the transaction which should be processed. (required)
	 * @param int $payment_method_configuration_id The payment method configuration ID which is applied to the transaction. (required)
	 * @param \WhiteLabelMachineName\Sdk\Model\TokenizedCardDataCreate $card_data The card details as JSON in plain which should be used to authorize the payment. (required)
	 * @throws \WhiteLabelMachineName\Sdk\ApiException
	 * @throws \WhiteLabelMachineName\Sdk\VersioningException
	 * @throws \WhiteLabelMachineName\Sdk\Http\ConnectionException
	 * @return ApiResponse
	 */
	public function processWith3DSecureWithHttpInfo($space_id, $transaction_id, $payment_method_configuration_id, $card_data) {
		// verify the required parameter 'space_id' is set
		if (is_null($space_id)) {
			throw new \InvalidArgumentException('Missing the required parameter $space_id when calling processWith3DSecure');
		}
		// verify the required parameter 'transaction_id' is set
		if (is_null($transaction_id)) {
			throw new \InvalidArgumentException('Missing the required parameter $transaction_id when calling processWith3DSecure');
		}
		// verify the required parameter 'payment_method_configuration_id' is set
		if (is_null($payment_method_configuration_id)) {
			throw new \InvalidArgumentException('Missing the required parameter $payment_method_configuration_id when calling processWith3DSecure');
		}
		// verify the required parameter 'card_data' is set
		if (is_null($card_data)) {
			throw new \InvalidArgumentException('Missing the required parameter $card_data when calling processWith3DSecure');
		}
		// header params
		$headerParams = [];
		$headerAccept = $this->apiClient->selectHeaderAccept([]);
		if (!is_null($headerAccept)) {
			$headerParams[HttpRequest::HEADER_KEY_ACCEPT] = $headerAccept;
		}
		$headerParams[HttpRequest::HEADER_KEY_CONTENT_TYPE] = $this->apiClient->selectHeaderContentType(['application/json;charset=utf-8']);

		// query params
		$queryParams = [];
		if (!is_null($space_id)) {
			$queryParams['spaceId'] = $this->apiClient->getSerializer()->toQueryValue($space_id);
		}
		if (!is_null($transaction_id)) {
			$queryParams['transactionId'] = $this->apiClient->getSerializer()->toQueryValue($transaction_id);
		}
		if (!is_null($payment_method_configuration_id)) {
			$queryParams['paymentMethodConfigurationId'] = $this->apiClient->getSerializer()->toQueryValue($payment_method_configuration_id);
		}

		// path params
		$resourcePath = '/card-processing/processWith3DSecure';
		// default format to json
		$resourcePath = str_replace('{format}', 'json', $resourcePath);

		// form params
		$formParams = [];
		// body params
		$tempBody = null;
		if (isset($card_data)) {
			$tempBody = $card_data;
		}

		// for model (json/xml)
		$httpBody = '';
		if (isset($tempBody)) {
			$httpBody = $tempBody; // $tempBody is the method argument, if present
		} elseif (!empty($formParams)) {
			$httpBody = $formParams; // for HTTP post (form)
		}
		// make the API Call
		try {
			$response = $this->apiClient->callApi(
				$resourcePath,
				'POST',
				$queryParams,
				$httpBody,
				$headerParams,
				'string',
				'/card-processing/processWith3DSecure'
            );
			return new ApiResponse($response->getStatusCode(), $response->getHeaders(), $this->apiClient->getSerializer()->deserialize($response->getData(), 'string', $response->getHeaders()));
		} catch (ApiException $e) {
			switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        'string',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\ClientError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
                case 442:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\ClientError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
                case 542:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\WhiteLabelMachineName\Sdk\Model\ServerError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                break;
			}
			throw $e;
		}
	}


}
