<?php

declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Routing\Router;
use Exception;

class FacebookComponent extends Component
{
    protected $_defaultConfig = [];

    const FB_GRAPH_VERSION = "";
    const FB_APP_ID = "";
    const FB_APP_SECRET = "";
    const FB_REDIRECT_URI = "";
    const FB_APP_STATE = "";

    private $InfoToken = null;
    private $InfoCode = null;
    private $summary = null;
    private $configFB = null;
    private $base_url = null;
    private function setInfoCode($infoCode)
    {
        $this->InfoCode = $infoCode;
    }

    private function getInfoCode()
    {
        return $this->InfoCode;
    }

    private function setConfigParamsFB($key, $value)
    {

        $this->configFB[$key] = $value;
    }



    private function getConfigParamsFB($key)
    {
        return $this->configFB[$key] ?? '';
    }

    private function setInfoToken($data)
    {
        $this->InfoToken = $data;
    }
    private function getInfoToken()
    {
        return $this->InfoToken;
    }

    private function setSummary($summary)
    {
        $this->summary = $summary;
    }

    private function getSummary()
    {
        return $this->summary;
    }

    public function setBaseUrl($url)
    {
        $this->base_url =  $url;
    }

    private function getBaseUrl()
    {
        if (empty($this->base_url)) {
            return Router::url('/', true);
        }

        return $this->base_url;
    }

    private function getUrlFB()
    {

        return 'https://www.facebook.com/' .  $this->getConfigParamsFB('FB_GRAPH_VERSION');
    }

    public function initialize(array $config): void
    {

        parent::initialize($config);

        $this->setConfigParamsFB('FB_GRAPH_VERSION', env('FB_GRAPH_VERSION'));
        $this->setConfigParamsFB('FB_APP_ID', env('FB_APP_ID'));
        $this->setConfigParamsFB('FB_APP_SECRET', env('FB_APP_SECRET'));
        $this->setConfigParamsFB('FB_REDIRECT_URI', env('FB_REDIRECT_URI'));
    }

    private function deleteFacebookApiCal($endpoint, $params)
    {
        try {



            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

            // get curl response, json decode it, and close curl
            $fbResponse = curl_exec($ch);
            curl_close($ch);

            $fbResponse = json_decode($fbResponse, TRUE);
        } catch (\Exception $e) {
            throw $e;
        }



        return array( // return response data
            'endpoint' => $endpoint,
            'params' => $params,
            'has_errors' => isset($fbResponse['error']) ? TRUE : FALSE, // boolean for if an error occured
            'error_message' => isset($fbResponse['error']) ? $fbResponse['error']['message'] : '', // error message
            'fb_response' => $fbResponse // actual response from the call
        );
    }

    private function postFacebookApiCall($endpoint, $params)
    {

        try {



            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, 1);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

            // get curl response, json decode it, and close curl
            $fbResponse = curl_exec($ch);
            curl_close($ch);

            $fbResponse = json_decode($fbResponse, TRUE);
        } catch (\Exception $e) {
            throw $e;
        }



        return array( // return response data
            'endpoint' => $endpoint,
            'params' => $params,
            'has_errors' => isset($fbResponse['error']) ? TRUE : FALSE, // boolean for if an error occured
            'error_message' => isset($fbResponse['error']) ? $fbResponse['error']['message'] : '', // error message
            'fb_response' => $fbResponse // actual response from the call
        );
    }

    private function makeFacebookApiCall($endpoint, $params)
    {

        try {

            /**
             * Para otimizar as consultas, VERIFICAR se a mesma já foi executada e está em cache por isso esse IF
             */
            $cacheName = md5($endpoint . '?' . http_build_query($params));
            $cacheResult = Cache::read($cacheName);

            $cacheResult = null; //NAO USAR CACHE -- COMENTAR ESSA FUNCAO DEPOIS DOS TESTES

            if (empty($cacheResult)) {

                // open curl call, set endpoint and other curl params
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint . '?' . http_build_query($params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                // get curl response, json decode it, and close curl
                $fbResponse = curl_exec($ch);
                curl_close($ch);

                //Armazenar a consulta em cache
                Cache::write($cacheName, $fbResponse, 'imoads');
            } else {
                $fbResponse = $cacheResult;
            }



            $fbResponse = json_decode($fbResponse, TRUE);
        } catch (\Exception $e) {


            throw $e;
        }


        return array( // return response data
            'endpoint' => $endpoint,
            'params' => $params,
            'has_errors' => isset($fbResponse['error']) ? TRUE : FALSE, // boolean for if an error occured
            'error_message' => isset($fbResponse['error']) ? $fbResponse['error']['message'] : '', // error message
            'fb_response' => $fbResponse // actual response from the call
        );
    }

    /**
     * GERAR URL DE AUTENTICAÇÃO
     */
    public function getURLAuth()
    {

        $endpoint = $this->getUrlFB() . '/dialog/oauth';

        $arrScope = [
            'public_profile',
            'email',
            'instagram_basic',
            'ads_management',
            'business_management',
            'pages_show_list',
        ];

        $params = array(
            'client_id' => $this->getConfigParamsFB('FB_APP_ID'),
            'redirect_uri' => $this->getBaseUrl()  . $this->getConfigParamsFB('FB_REDIRECT_URI'),
            'state' => $this->getConfigParamsFB('FB_APP_STATE'),
            'scope' => $arrScope
        );

        $url = $endpoint . '?' . http_build_query($params);
        return $url;
    }


    public function getToken($code)
    {

        $endpoint = $this->getUrlFB() . '/oauth/access_token';
        $params = array(
            'client_id' => $this->getConfigParamsFB('FB_APP_ID'),
            'redirect_uri' => $this->getBaseUrl() . $this->getConfigParamsFB('FB_REDIRECT_URI'),
            'client_secret' => $this->getConfigParamsFB('FB_APP_SECRET'),
            'code' => $code
        );

        $response =  $this->makeFacebookApiCall($endpoint, $params);
        if ($response['has_errors']) {
            throw new \Exception($response['error_message']);
            return null;
        }

        $this->setInfoToken($response['fb_response']);
        return $response['fb_response']['access_token'];
    }

    /**
     * LISTAR TODAS AS PÁGINAS DO USUÁRIO
     */
    public function getAllAccountFBPage($token)
    {
        $endpoint = $this->getUrlFB() . '/me/accounts';

        $params = array(
            'fields' => 'id,followers_count,name,fan_count,picture{url}',
            'sort' => 'name_ascending',
            'limit' => 999,
            'access_token' => $token,
        );

        $response =  $this->makeFacebookApiCall($endpoint, $params);

        if ($response['has_errors']) {
            throw new \Exception($response['error_message']);
        }

        $arrAccounts = [];
        foreach ($response['fb_response']['data'] as $account) {
            $account['pic'] = $account['picture']['data']['url'] ?? null;
            $arrAccounts[] = $account;
        }

        return $arrAccounts;
    }

    /**
     * RECEBER Insights DE ANÚNCIOS
     */
    public function getInsights($token, $account_id, $data_inicio = null, $data_final = null, $active = false)
    {


        $endpoint = $this->getUrlFB() . '/' . $account_id . '/insights/';
        $params = [
            'fields' => 'account_name,impressions,reach,clicks,spend,actions',
            'time_range' =>  ['since' => $data_inicio, 'until' => $data_final],
            'access_token' => $token
        ];

        $response =  $this->makeFacebookApiCall($endpoint, $params);
        if ($response['has_errors']) {
            throw new \Exception($response['error_message']);
        }

        return $response['fb_response']['data'] ?? [];
    }

    /**
     * BUSCAR PÚBLICOS OCULTOS DA LISTA DO FB
     */
    public function getTargetInteress($token, $name = 'imoveis')
    {

        $endpoint = $this->getUrlFB() . '/search';
        $params = [
            'type' => 'adinterest',
            'q' => $name,
            'access_token' => $token,
        ];

        $response =  $this->makeFacebookApiCall($endpoint, $params);

        if ($response['has_errors']) {
            throw new \Exception(json_encode($response['fb_response']));
        }

        return $response['fb_response']['data'] ?? [];

    }
}
