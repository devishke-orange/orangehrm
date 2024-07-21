<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\FreeTrial\Service;

use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\FreeTrial\Dao\FreeTrialDao;

class FreeTrialService
{
    use ConfigServiceTrait;

    public const TRIAL_CREATED_DATE_CONFIG = 'instance.created_at'; //store in date time
    public const TRIAL_PERIOD_CONFIG = 'trial.period'; //store as 30 (days)
    public const ON_DEMAND_URL_CONFIG = 'ondemand.url';
    public const INSTANCE_NAME_CONFIG = 'instance.name';
    public const TRIAL_SUBSCRIBED_DATE_CONFIG = 'trial.subscribed_at';
    public const TRIAL_ON_DEMAND_CLIENT_ID_CONFIG = 'trial.ondemand_client_id';
    public const TRIAL_ON_DEMAND_CLIENT_SECRET_CONFIG = 'trial.ondemand_client_secret';
    public const DEFAULT_CONTENT_TYPE = "application/json";
    public const ENDPOINT_BASE_PATH = '/api/instance/v1/instance';

    /**
     * @var FreeTrialDao
     */
    private FreeTrialDao $freeTrialDao;

    /**
     * @var ClientService|null
     */
    private ?ClientService $clientService = null;

    /**
     * @return FreeTrialDao
     */
    public function getFreeTrialDao(): FreeTrialDao
    {
        return $this->freeTrialDao ??= new FreeTrialDao();
    }

    /**
     * @return ClientService
     */
    public function getClientService(): ClientService
    {
        if (!$this->clientService instanceof ClientService) {
            $this->clientService = new ClientService();
        }
        return $this->clientService;
    }

    /**
     * @return string
     */
    public function getRemainingDays(): string
    {
        $trialPeriod = $this->getConfigService()->getConfigDao()->getValue(self::TRIAL_PERIOD_CONFIG);
        $instanceCreatedDate = $this->getConfigService()->getConfigDao()->getValue(self::TRIAL_CREATED_DATE_CONFIG);

        $remainingDays =  $trialPeriod - (round((strtotime(date('Y-m-d H:i:s')) - strtotime($instanceCreatedDate)) / (86400)));
        if ($remainingDays < 0) {
            $remainingDays = 0;
        }

        if ($remainingDays == '1') {
            return $remainingDays . ' Day';
        } else {
            return $remainingDays . ' Days';
        }
    }

    /**
     * @return bool
     */
    public function isSubscribed(): bool
    {
        if (($this->getConfigService()->getConfigDao()->getValue(self::TRIAL_SUBSCRIBED_DATE_CONFIG)) != null) {
            return true;
        }
        return false;
    }

    /**
     * @return string|null
     */
    public function getInstanceName(): ?string
    {
        return $this->getConfigService()->getConfigDao()->getValue(self::INSTANCE_NAME_CONFIG);
    }

    /**
     * @return string
     */
    public function getInstanceUrl(): ?string
    {
        return $this->getConfigService()->getConfigDao()->getValue(self::ON_DEMAND_URL_CONFIG);
    }

    /**
     * @return string
     */
    public function getOnDemandClientId(): string
    {
        return $this->getConfigService()->getConfigDao()->getValue(self::TRIAL_ON_DEMAND_CLIENT_ID_CONFIG);
    }

    /**
     * @return string
     */
    public function getOnDemandClientSecret(): string
    {
        return $this->getConfigService()->getConfigDao()->getValue(self::TRIAL_ON_DEMAND_CLIENT_SECRET_CONFIG);
    }

    public function setSubscribed(): void
    {
        $this->getFreeTrialDao()->saveSubscribeDate();
    }

    /**
     * @return array
     */
    public function getPreloadedValues(): array
    {
        $instanceName = $this->getInstanceName();
        $onDemandUrl = $this->getInstanceUrl();
        $token = $this->getClientService()->getAccessToken();
        $headers = [
            'Accept' => self::DEFAULT_CONTENT_TYPE,
            'Content-Type' => self::DEFAULT_CONTENT_TYPE,
            'Authorization' => 'Bearer ' . $token
        ];

        $response = $this->getClientService()->getApiClient()->get(
            $onDemandUrl . self::ENDPOINT_BASE_PATH . '/details?instanceName=' . $instanceName,
            [
                'headers' => $headers
            ]
        );

        $body = json_decode($response->getBody(), true);
        $responseCode = $response->getStatusCode();

        return [
            'responseCode' => $responseCode,
            'response' => $body,
        ];
    }

    /**
     * @param array $queryParams
     * @return array|null
     */
    public function saveSubscription(array $queryParams): ?array
    {
        $onDemandUrl = $this->getInstanceUrl();
        $token = $this->getClientService()->getAccessToken();
        $headers = [
            'Accept' => self::DEFAULT_CONTENT_TYPE,
            'Content-Type' => self::DEFAULT_CONTENT_TYPE,
            'Authorization' => 'Bearer ' . $token
        ];
        $requestData = [
            'companyName' => $queryParams['companyName'],
            'contactNumber' => $queryParams['contactNumber'],
            'contactPersonName' => $queryParams['contactPersonName'],
            'country' => $queryParams['country'],
            'email' => $queryParams['email'],
            'noOfEmployees' => $queryParams['noOfEmployees']
        ];
        try {
            $response = $this->getClientService()->getApiClient()->post(
                $onDemandUrl . self::ENDPOINT_BASE_PATH . '/subscribe',
                [
                    'headers' => $headers,
                    'body' => json_encode($requestData)
                ]
            );
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            }
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }

        return json_decode('');
    }
}