<?php
declare(strict_types=1);

/**
 * Description:
 *     Handy methods to perform general PDO operations over the database.
 *
 * @package  SnappyShopperTestNoFramework\DBPDO
 * @author   Andrés Rodríguez
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/andrewf137/technologi-backend-test
 */

namespace Src\System;

use PDO;
use PDOException;
use PDOStatement;

class DBPDO
{
    public const PROPERTY_API = 'https://trial.craig.mtcserver15.com/api/properties';

    /** @var PDO */
    public $pdo;

    /**
     * DBPDO constructor.
     */
    function __construct()
    {
        $this->connect();
    }

    /**
     * @return bool
     */
    function connect(): bool
    {
        $params = require 'config' . DIRECTORY_SEPARATOR . 'config.php';

        if(!$this->pdo){
            $dsn      = 'mysql:dbname=' . $params['DATABASE_NAME'] .
                        ';host=' . $params['DATABASE_HOST'] .
                        ';charset=' . $params['DATABASE_CHARSET'];
            $user     = $params['DATABASE_USER'];
            $password = $params['DATABASE_PASS'];

            try {
                $this->pdo = new PDO($dsn, $user, $password, [PDO::ATTR_PERSISTENT => true]);
                return true;
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }else{
            $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
            return true;
        }
    }

    /**
     * @param string $url
     * @return array
     */
    public function getPropertyDataByUrl(string $url): array
    {
        return json_decode(file_get_contents($url), TRUE);
    }

    /**
     * @param array $properties
     * @param string $nextPageUrl
     * @param string $lastPageUrl
     * @param int $pageFrom
     * @param int $pageTo
     */
    public function saveProperties (
        array $properties,
        string $nextPageUrl,
        string $lastPageUrl,
        int $pageFrom,
        int $pageTo): void
    {
        /** @var int $i */
        $i = 1;
        /** @var array $property */
        foreach ($properties as $property) {
            $this->saveProperty($property);
        }

        if ($nextPageUrl !== $lastPageUrl && $pageFrom !== $pageTo) {
            $nextPage = $this->getPropertyDataByUrl($nextPageUrl);
            $this->saveProperties(
                $nextPage['data'],
                $nextPage['next_page_url'],
                $lastPageUrl,
                $nextPage['current_page'],
                $pageTo
            );
        }
    }

    /**
     * @param array $propertyData
     */
    private function saveProperty(array $propertyData): void
    {
        $query = "SELECT * FROM properties WHERE property_identifier = ? LIMIT 1";
        $propertyEntity = $this->fetch($query, $propertyData['uuid']);

        $parameters = [
            $propertyData['country'],
            $propertyData['town'],
            $propertyData['description'],
            (float)$propertyData['latitude'],
            (float)$propertyData['longitude'],
            (int)$propertyData['num_bedrooms'],
            (int)$propertyData['num_bathrooms'],
            (float)$propertyData['price'],
            \json_encode($propertyData['property_type'], JSON_UNESCAPED_SLASHES),
            $propertyData['type'],
            $propertyData['uuid']
        ];

        if ($propertyEntity && !empty($propertyEntity)) {
            $query = "UPDATE properties
                         SET country = ?,
                             town = ?,
                             description = ?,
                             latitude = ?,
                             longitude = ?,
                             num_bedrooms = ?,
                             num_bathrooms = ?,
                             price = ?,
                             property_type = ?,
                             type = ?
                       WHERE property_identifier = ?";
        } else {
            $query = "INSERT INTO properties (country, town, description, latitude, longitude, num_bedrooms, num_bathrooms, price, property_type, type, property_identifier)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        $this->execute($query, $parameters);
    }

    /**
     * @return array
     */
    public function getTopAgents(): array
    {
       /** @var string $sql */
        $query = "SELECT a.agent1 AS agent
                    FROM (
                        -- Agents with at least two properties in common with at least one agent
                          SELECT ap1.agent as agent1, ap2.agent
                            FROM agent_properties ap1
                            JOIN agent_properties ap2 ON ap2.property_id = ap1.property_id
                           WHERE ap1.agent != ap2.agent
                           GROUP BY ap1.agent, ap2.agent
                           HAVING COUNT(*) > 1
                  ) a
                  GROUP BY a.agent1
                  HAVING COUNT(*) > 1";

        return $this->fetchAll($query);
    }

    /**
     * Add few random agents-properties relations
     */
    public function loadFixtures(): void
    {
        /**
         * Set id to 1 after db deletion
         */

        $query = "ALTER TABLE `properties` AUTO_INCREMENT = 1";
        $this->execute($query);

        /**
         * Add property types
         */

        $query = "INSERT INTO property_types (name) VALUES (?), (?)";
        $this->execute($query, ['sale', 'rent']);

        /**
         * Save 7 properties into the database
         */

        /** @var array $propertiesData */
        $propertiesData = $this->getPropertyDataByUrl(
            \sprintf(
                self::PROPERTY_API . '?page[size]=%s&page[number]=%s',
                7,
                1
            )
        );

        $this->saveProperties(
            $propertiesData['data'],
            $propertiesData['next_page_url'],
            $propertiesData['last_page_url'],
            1,
            1
        );

        /**
         * Save 7 agents
         */

        /** @var array $agentNames */
        $agentNames = [
            'Jemimah Barnett',
            'Monica Davis',
            'Lonee Hamilton',
   	        'Roy Brooks',
   	        'Ian Green',
            'Knight Frank',
            'Daniel Cobb'
        ];

        /** @var string $name */
        foreach ($agentNames as $name) {
            $query = "SELECT * FROM agents WHERE name = ? LIMIT 1";
            $agent = $this->fetch($query, $name);

            if (!$agent || empty($agent)) {
                $query = "INSERT INTO agents (name) VALUES (?)";
                $this->execute($query, $name);

                /**
                 * Add from one to three random properties to each agent
                 */
                for ($i = 1; $i <=3; $i++) {
                    /** @var int|null $propertyId */
                    $propertyId = $this->getRandomProperty();

                    if (isset($propertyId['id'])) {
                        $query = "INSERT INTO agent_properties (property_id, agent) VALUES (?, ?)";
                        $this->execute($query, [$propertyId['id'], $name]);
                    }
                }
            }
        }
    }

    private function getRandomProperty()
    {
        $query = "SELECT id FROM properties
                 ORDER BY RAND()
                 LIMIT 1";

        return $this->fetch($query);
    }

    /**
     * @param $query
     * @return PDOStatement|bool
     */
    function prep_query($query)
    {
        return $this->pdo->prepare($query);
    }

    /**
     * @param $query
     * @param null $values
     * @return PDOStatement|bool
     */
    function execute($query, $values = null)
    {
        if (null === $values) {
            $values = [];
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        /** @var PDOStatement|bool $stmt */
        $stmt = $this->prep_query($query);
        $stmt->execute($values);
        $error = $stmt->errorInfo();
        return $stmt;
    }

    /**
     * @param string $query
     * @param null $values
     * @return mixed
     */
    function fetch(string $query, $values = null)
    {
        if (null === $values){
            $values = [];
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        /** @var PDOStatement|bool $stmt */
        $stmt = $this->execute($query, $values);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $query
     * @param null $values
     * @param string|null $key
     * @return array
     */
    function fetchAll(string $query, $values = null, ?string $key = null): array
    {
        if (null === $values){
            $values = [];
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        /** @var PDOStatement|bool $stmt */
        $stmt = $this->execute($query, $values);
        /** @var array $results */
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Allows the user to retrieve results using a
        // column from the results as a key for the array
        if (null !== $key && !empty($results) && $results[0][$key]) {
            $keyed_results = [];
            foreach($results as $result){
                $keyed_results[$result[$key]] = $result;
            }
            $results = $keyed_results;
        }

        return $results;
    }
}
