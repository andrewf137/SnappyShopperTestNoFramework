<?php
declare(strict_types=1);

/**
 * Description:
 *     Initiator so test can be run with no preparation.
 *
 * @author   Andrés Rodríguez
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/andrewf137/technologi-backend-test
 */

require_once(__DIR__. DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

// Build DB with user and password and tables
$dbBuilder = new Src\System\DBBuilder();
$dbBuilder->buildDB();

// Create some random agents-properties relations
$dbBuilder = new Src\System\DBPDO();
$dbBuilder->loadFixtures();
