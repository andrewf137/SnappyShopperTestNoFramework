<?php
declare(strict_types=1);

/**
 * Description:
 *     Create db and tables to run the test.
 *
 * @package  SnappyShopperTestNoFramework\DBBuilder
 * @author   Andrés Rodríguez
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/andrewf137/SnappyShopperTestNoFramework
 */

namespace Src\System;

use PDO;

/**
 * Class DBBuilder
 *
 * @package  SnappyShopperTestNoFramework\DBBuilder
 */
class DBBuilder
{
    /**
     * @desc Using data in config/config.php file, a db plus two tables will be created
     */
    public function buildDB()
    {
        // Retrieve DB parameters
	$params = require 'config' . DIRECTORY_SEPARATOR . 'config.php';
	$db = $params['DATABASE_NAME'];
	$host = $params['DATABASE_HOST'];
	$port = $params['DATABASE_PORT'];
	$user = $params['DATABASE_USER'];
	$pass = $params['DATABASE_PASS'];
	$charset = $params['DATABASE_CHARSET'];

	// Create a database server connection without specifying db name
        $dsn = "mysql:host=$host;port=$port;charset=$charset;";
	$this->pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_PERSISTENT => true]);

	// Drop schema in case it exists
	$this->pdo->exec("DROP SCHEMA $db;");

	// Create db
	$this->pdo->exec("
            CREATE DATABASE `$db`;
            CREATE USER '$user'@'$host' IDENTIFIED BY '$pass';
            GRANT ALL ON `$db`.* TO '$user'@'$host';
            FLUSH PRIVILEGES;
	");

	// Select db just created
        $this->pdo->exec("USE $db;");

        $this->pdo->exec('CREATE TABLE agents (name VARCHAR(32) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->pdo->exec('CREATE TABLE agent_properties (agent VARCHAR(32) NOT NULL, property_id INT NOT NULL, INDEX IDX_F685E0C8268B9C9D (agent), INDEX IDX_F685E0C8549213EC (property_id), PRIMARY KEY(agent, property_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->pdo->exec('CREATE TABLE properties (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(32) DEFAULT NULL, property_identifier CHAR(36) NOT NULL, country VARCHAR(255) DEFAULT NULL, town VARCHAR(255) DEFAULT NULL, description VARCHAR(2000) DEFAULT NULL, latitude NUMERIC(10, 8) NOT NULL, longitude NUMERIC(11, 8) NOT NULL, num_bedrooms INT DEFAULT 0 NOT NULL, num_bathrooms INT DEFAULT 0 NOT NULL, price NUMERIC(15, 2) DEFAULT \'0\' NOT NULL, property_type JSON DEFAULT NULL, INDEX IDX_87C331C78CDE5729 (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->pdo->exec('CREATE TABLE property_types (name VARCHAR(32) NOT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->pdo->exec('ALTER TABLE agent_properties ADD CONSTRAINT FK_F685E0C8268B9C9D FOREIGN KEY (agent) REFERENCES agents (name) ON DELETE CASCADE');
        $this->pdo->exec('ALTER TABLE agent_properties ADD CONSTRAINT FK_F685E0C8549213EC FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE');
        $this->pdo->exec('ALTER TABLE properties ADD CONSTRAINT FK_87C331C78CDE5729 FOREIGN KEY (type) REFERENCES property_types (name)');
    }
}
