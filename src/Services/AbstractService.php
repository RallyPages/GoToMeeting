<?php
/**
 * Abstract service implementation.
 */
namespace kenobi883\GoToMeeting\Services;

/**
 * Abstract service implementation. Additional services should extend this class.
 */
class AbstractService
{
    /**
     * @var string root URL for authorizing requests
     */
    protected $endpoint = '';

    /**
     * @var \kenobi883\GoToMeeting\Client
     */
    protected $client;

    /**
     * Default constructor.
     *
     * @param \kenobi883\GoToMeeting\Client $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }
}
