<?php

use App\Kernel;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

$user = $entityManager->getRepository(User::class)->findOneBy([], ['id' => 'DESC']);

echo "<pre>";
if ($user) {
    echo "Last User Found:\n";
    echo "ID: " . $user->getId() . "\n";
    echo "Email: " . $user->getEmail() . "\n";
    echo "Roles: " . print_r($user->getRoles(), true) . "\n";
    echo "Type: " . get_class($user) . "\n";
    echo "Password Hash: " . substr($user->getPassword(), 0, 20) . "...\n";
} else {
    echo "No users found in database.\n";
}
echo "</pre>";
