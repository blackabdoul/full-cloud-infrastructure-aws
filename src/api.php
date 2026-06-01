<!--`api.php` — EC2 Backend -->
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require '/var/www/html/vendor/autoload.php';
use Aws\SecretsManager\SecretsManagerClient;

try {
    // Retrieve credentials from Secrets Manager via IAM role — no hardcoded keys
    $client = new SecretsManagerClient(['region' => 'us-east-1', 'version' => 'latest']);
    $result = $client->getSecretValue(['SecretId' => 'rds/cloud-project/credentials']);
    $secret = json_decode($result['SecretString'], true);

    $pdo = new PDO(
        "mysql:host=cloud-project-db.cy7iu4ws6euf.us-east-1.rds.amazonaws.com;dbname=appdb",
        $secret['username'],
        $secret['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM products ORDER BY id");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

