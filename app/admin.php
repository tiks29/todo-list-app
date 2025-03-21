<?php
session_start();
require_once 'vendor/autoload.php';

use Config\DependencyContainer;
use Models\User;

$container = new DependencyContainer();

$db = $container->get('database');
$authService = $container->get('authService');

// Check if user is logged in and has admin role
if (!$authService->isLoggedIn() || !$authService->hasRole('admin')) {
    header('Location: index.php');
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_GET['user_id'] ?? 0;

// Handle user actions
if ($action === 'delete' && $userId > 0) {
    $user = new User($userId);
    $user->delete();
    header('Location: admin.php');
    exit;
} elseif ($action === 'promote' && $userId > 0) {
    $user = new User($userId);
    $user->addRole('admin');
    header('Location: admin.php');
    exit;
} elseif ($action === 'demote' && $userId > 0) {
    $user = new User($userId);
    $user->removeRole('admin');
    header('Location: admin.php');
    exit;
}

// Get all users
$users = User::getAllUsers($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ToDo App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#6b7280',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <h1 class="text-xl font-bold text-primary">ToDo App</h1>
                </div>
            </div>
            <div class="flex items-center">
                <a href="index.php" class="mr-4 text-primary hover:text-primary-dark">Back to App</a>
                <a href="logout.php" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Admin Panel</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ID
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Username
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Roles
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created At
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $user['id']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php
                            $roles = $user['roles'] ? explode(',', $user['roles']) : [];
                            foreach ($roles as $role) {
                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 mr-1">' . htmlspecialchars($role) . '</span>';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if (strpos($user['roles'], 'admin') !== false): ?>
                                <a href="admin.php?action=demote&user_id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    Remove Admin
                                </a>
                            <?php else: ?>
                                <a href="admin.php?action=promote&user_id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    Make Admin
                                </a>
                            <?php endif; ?>

                            <a href="admin.php?action=delete&user_id=<?php echo $user['id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this user?')"
                               class="text-red-600 hover:text-red-900">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>

