<?php

namespace NextCaller;

use Guzzle\Http\Message\ResponseInterface;
use NextCaller\Exception\FormatException;
use NextCaller\Exception\NoContentException;
use NextCaller\Exception\NotFoundException;

class Client
{
    private $__formats = array('json', 'xml');

    /** @var \Guzzle\Http\Client */
    protected static $_client;
    /** @var string */
    protected static $_auth;
    /** @var string */
    protected static $_format = 'json';
    /** @var string */
    protected static $_url;

    /**
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $format
     * @param string $_url
     */
    public function __construct($apiKey, $apiSecret, $_url = 'https://api.nextcaller.com/v2/') {
        if (empty($apiKey)) {
            $apiKey = getenv('NC_API_KEY');
        }
        if (empty($apiSecret)) {
            $apiSecret = getenv('NC_API_SECRET');
        }
        if (empty(self::$_client)) {
            self::$_client = new \Guzzle\Http\Client();
        }
        return $this->setBasicAuth($apiKey, $apiSecret)->setUrl($_url);
    }

    public function addSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $client){
        self::$_client->addSubscriber($client);
    }

    public function removeSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $client){
        self::$_client->getEventDispatcher()->removeSubscriber($client);
    }

    /**
     * @param string $apiKey
     * @param string $apiSecret
     * @return $this
     */
    public function setBasicAuth($apiKey, $apiSecret) {
        self::$_auth = array($apiKey, $apiSecret);
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url) {
        self::$_url = $url;
        return $this;
    }

    /**
     * @link https://dev.nextcaller.com/documentation/get-profile/
     * @param string $id
     * @return array
     */
    public function getProfile($id) {
        $response = $this->getProfileResponse($id);
        $response->setAuth(self::$_auth[0], self::$_auth[1]);
        return $this->proceedResponse($response);
    }

    /**
     * @param string $id
     * @internal param string $phone
     * @return ResponseInterface
     */
    public function getProfileResponse($id) {
        $options = array('query' => array('format' => self::$_format));
        return self::$_client->get(self::$_url . 'users/' . $id . '/', null, $options);
    }

    /**
     * @param string $phone
     * @return array
     */
    public function getProfileByPhone($phone) {
        $response = $this->getProfileByPhoneResponse($phone);
        $response->setAuth(self::$_auth[0], self::$_auth[1]);
        return $this->proceedResponse($response);
    }

    /**
     * @param string $phone
     * @return ResponseInterface
     */
    public function getProfileByPhoneResponse($phone) {
        $options = array('query' => array('phone' => $phone, 'format' => self::$_format));
        return self::$_client->get(self::$_url . 'records/',null, $options);
    }

    /**
     * @link https://dev.nextcaller.com/documentation/post-profile/
     * @param string $id
     * @param array
     * @return array
     */
    public function setProfile($id, $data) {
        $options = array('query' => array('format' => self::$_format));
        $response = self::$_client->post(self::$_url . 'users/' . $id . '/', null, json_encode($data), $options);
        $response->setAuth(self::$_auth[0], self::$_auth[1]);
        return $this->proceedResponse($response);
    }

    /**
     * @param \Guzzle\Http\Message\Request $response
     * @throws FormatException
     * @throws NotFoundException
     * @return array
     */
    protected function proceedResponse(\Guzzle\Http\Message\Request $response) {
        $response = $response->send();
        if ($response->getStatusCode() == 204) {
            return null;
        }
        if ($response->getStatusCode() == 404) {
            throw new NotFoundException($response->getReasonPhrase());
        }
        if ($response->getHeader('content-type') == 'application/json') {
            return $response->json();
        }
        throw new FormatException('Not valid response content type');
    }
}