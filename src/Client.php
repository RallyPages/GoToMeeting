<?php
/**
 * Client class for managing API requests.
 */
namespace kenobi883\GoToMeeting;

use GuzzleHttp\Subscriber\Log\LogSubscriber;
use kenobi883\GoToMeeting\Models\Auth;
use Psr\Log\LoggerInterface;

/**
 * Class Client.
 */
class Client
{
    /**
     * @var string root URL for authorizing requests
     */
    private $endpoint = 'https://api.citrixonline.com/G2M/rest';

    /**
     * @var string key to access the API
     */
    private $apiKey;

    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzleClient;

    /**
     * @var \kenobi883\GoToMeeting\Models\Auth
     */
    private $auth;

    /**
     * Default constructor.
     *
     * Configures the client for authenticating.
     *
     * @param string               $apiKey      client ID or API key
     * @param string|null          $accessToken optionally provide an obtained OAuth access token
     *                                          to configure the auth property
     * @param LoggerInterface|null $logger      logger implementation to log requests and responses against
     */
    public function __construct($apiKey = null, $accessToken = null, LoggerInterface $logger = null)
    {
        $this->apiKey = $apiKey;
        $this->guzzleClient = new \GuzzleHttp\Client([
            'base_uri' => $this->endpoint,
        ]);
        if ($accessToken !== null) {
            $auth = new Auth();
            $auth->setAccessToken($accessToken);
            $this->setAuth($auth);
        }
        if ($logger !== null) {
            $this->guzzleClient->getEmitter()->attach(new LogSubscriber($logger));
        }
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getGuzzleClient()
    {
        return $this->guzzleClient;
    }

    /**
     * @param \GuzzleHttp\Client $client
     */
    public function setGuzzleClient($client)
    {
        $this->guzzleClient = $client;
    }

    /**
     * @return Models\Auth
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param Models\Auth $auth
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle sending requests to the API. All responses returned as JSON.
     *
     * Request body sent as JSON.
     *
     * @param string $method        HTTP method for the request
     * @param string $path          relative URL to append to the root API endpoint
     * @param array  $query         optional data to send along with request
     * @param bool   $isAuthRequest optional flag to not pass the OAuth token with request
     *                              because we do not have it yet
     * @param array  $postBody      body content for a POST or PUT request
     *
     * @throws \GuzzleHttp\Exception\RequestException
     *
     * @return mixed
     */
    public function sendRequest($method, $path, array $query = null, $isAuthRequest = false, $postBody = null)
    {

        $guzzleClient = $this->getGuzzleClient();
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
        ];
        if (!$isAuthRequest && isset($this->auth)) {
            $accessToken = $this->auth->getAccessToken();
            $options['headers']['Authorization'] = "OAuth oauth_token={$accessToken}";

        }

        if ($query != null) {
            $options['query'] = $query;
        }
        if ($postBody != null && ($method == 'POST' || $method == 'PUT')) {
            $options['json'] = $postBody;
        }

        $path = "rest/{$path}";
        $request = $guzzleClient->request($method, $path, $options);
        $response = json_decode($request->getBody(), true);

        return $response;
    }
}
