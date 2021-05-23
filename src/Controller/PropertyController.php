<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\System\DBPDO;

class PropertyController {

    /** @var string */
    private $requestMethod;
    /** @var DBPDO */
    private $dbpdo;
    /** @var string */
    private $action;
    /** @var array */
    private $parameters;

    /**
     * PropertyController constructor.
     * @param string $requestMethod
     * @param string|null $action
     * @param array|null $parameters
     */
    public function __construct(string $requestMethod, string $action = null, array $parameters = null)
    {
        $this->requestMethod = $requestMethod;
        $this->dbpdo = new DBPDO();
        $this->action = $action;
        $this->parameters = $parameters;
    }

    public function processRequest()
    {
        if ('GET' !== $this->requestMethod) {
            $response = $this->notFoundResponse();
        } else {
            switch ($this->action) {
                case 'save-properties':
                    $response = $this->saveProperties();
                    break;
                case 'get-top-agents':
                    $response = $this->getTopAgents();
                    break;
                default:
                    $response = $this->notFoundResponse();
                    break;
            }
        }

        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function saveProperties()
    {
        /** @var array $propertiesData */
        $propertiesData = $this->dbpdo->getPropertyDataByUrl(
            \sprintf(
                DBPDO::PROPERTY_API . '?page[size]=%s&page[number]=%s',
                $this->parameters['perPage'],
                $this->parameters['page[from]']
            )
        );

        $this->dbpdo->saveProperties(
            $propertiesData['data'],
            $propertiesData['next_page_url'],
            $propertiesData['last_page_url'],
            (int)$this->parameters['page[from]'],
            (int)$this->parameters['page[to]']
        );

        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode(['Properties successfully created/updated']);;
        return $response;
    }

    private function getTopAgents(): array
    {
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($this->dbpdo->getTopAgents());
        return $response;
    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
}