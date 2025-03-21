<?php
session_start();
require_once 'vendor/autoload.php';

use Config\DependencyContainer;
use Config\Schema;

// Initialize container
$container = new DependencyContainer();

// Initialize database on first run
$db = $container->get('database');
$schema = new Schema($db);
$schema->initialize();

// Check if user is logged in
$authService = $container->get('authService');
$isLoggedIn = $authService->isLoggedIn();
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = $isLoggedIn && $authService->hasRole('admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDo App</title>
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
                <?php if ($isLoggedIn): ?>
                    <span class="mr-4">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <?php if ($isAdmin): ?>
                        <a href="admin.php" class="mr-4 text-primary hover:text-primary-dark">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="mr-4 text-primary hover:text-primary-dark">Login</a>
                    <a href="register.php" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if ($isLoggedIn): ?>
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">My ToDo List</h2>

            <div class="mb-6">
                <form id="todo-form" class="flex gap-2">
                    <input type="text" id="todo-title" placeholder="What needs to be done?"
                           class="flex-1 border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add
                    </button>
                </form>
            </div>

            <div id="todos-container">
                <div class="flex justify-between mb-4">
                    <h3 class="text-lg font-semibold">Tasks</h3>
                    <div>
                        <button id="filter-all" class="text-sm px-3 py-1 rounded bg-primary text-white mr-2">All</button>
                        <button id="filter-active" class="text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 mr-2">Active</button>
                        <button id="filter-completed" class="text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">Completed</button>
                    </div>
                </div>

                <ul id="todo-list" class="divide-y divide-gray-200">
                    <!-- Todo items will be inserted here -->
                </ul>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white shadow-md rounded-lg p-6 text-center">
            <h2 class="text-2xl font-bold mb-4">Welcome to the ToDo App</h2>
            <p class="mb-6">Please login or register to manage your tasks.</p>
            <div class="flex justify-center gap-4">
                <a href="login.php" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Login
                </a>
                <a href="register.php" class="bg-white hover:bg-gray-100 text-primary font-bold py-2 px-6 rounded border border-primary">
                    Register
                </a>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php if ($isLoggedIn): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const todoForm = document.getElementById('todo-form');
            const todoTitleInput = document.getElementById('todo-title');
            const todoList = document.getElementById('todo-list');
            const filterAll = document.getElementById('filter-all');
            const filterActive = document.getElementById('filter-active');
            const filterCompleted = document.getElementById('filter-completed');

            let currentFilter = 'all';

            // Load todos
            loadTodos();

            // Add new todo
            todoForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const title = todoTitleInput.value.trim();
                if (!title) return;

                fetch('api/todos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ title })
                })
                    .then(response => response.json())
                    .then(todo => {
                        todoTitleInput.value = '';
                        loadTodos();
                    })
                    .catch(error => console.error('Error:', error));
            });

            // Filter todos
            filterAll.addEventListener('click', function() {
                setActiveFilter('all');
                loadTodos();
            });

            filterActive.addEventListener('click', function() {
                setActiveFilter('active');
                loadTodos();
            });

            filterCompleted.addEventListener('click', function() {
                setActiveFilter('completed');
                loadTodos();
            });

            // Load todos from API
            function loadTodos() {
                fetch('api/todos.php?filter=' + currentFilter)
                    .then(response => response.json())
                    .then(todos => {
                        renderTodos(todos);
                    })
                    .catch(error => console.error('Error:', error));
            }

            // Render todos
            function renderTodos(todos) {
                todoList.innerHTML = '';

                if (todos.length === 0) {
                    todoList.innerHTML = '<li class="py-4 text-center text-gray-500">No tasks found</li>';
                    return;
                }

                todos.forEach(todo => {
                    const li = document.createElement('li');
                    li.className = 'py-4';
                    li.innerHTML = `
                        <div class="flex items-center">
                            <input type="checkbox" id="todo-${todo.id}"
                                   class="h-5 w-5 text-primary rounded border-gray-300 focus:ring-primary"
                                   ${todo.completed == 1 ? 'checked' : ''}>
                            <label for="todo-${todo.id}" class="ml-3 block text-gray-900 ${todo.completed == 1 ? 'line-through text-gray-500' : ''}">
                                ${todo.title}
                            </label>
                            <button data-id="${todo.id}" class="delete-todo ml-auto text-red-600 hover:text-red-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    `;
                    todoList.appendChild(li);

                    // Add event listener for checkbox
                    const checkbox = li.querySelector(`#todo-${todo.id}`);
                    checkbox.addEventListener('change', function() {
                        updateTodoStatus(todo.id, this.checked);
                    });

                    // Add event listener for delete button
                    const deleteButton = li.querySelector('.delete-todo');
                    deleteButton.addEventListener('click', function() {
                        deleteTodo(this.dataset.id);
                    });
                });
            }

            // Update todo status
            function updateTodoStatus(id, completed) {
                fetch('api/todos.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        title: document.querySelector(`label[for="todo-${id}"]`).textContent.trim(),
                        completed: completed ? 1 : 0
                    })
                })
                    .then(response => response.json())
                    .then(() => loadTodos())
                    .catch(error => console.error('Error:', error));
            }

            // Delete todo
            function deleteTodo(id) {
                if (confirm('Are you sure you want to delete this task?')) {
                    fetch(`api/todos.php?id=${id}`, {
                        method: 'DELETE'
                    })
                        .then(response => response.json())
                        .then(() => loadTodos())
                        .catch(error => console.error('Error:', error));
                }
            }

            // Set active filter
            function setActiveFilter(filter) {
                currentFilter = filter;

                // Update filter button styles
                filterAll.className = filter === 'all'
                    ? 'text-sm px-3 py-1 rounded bg-primary text-white mr-2'
                    : 'text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 mr-2';

                filterActive.className = filter === 'active'
                    ? 'text-sm px-3 py-1 rounded bg-primary text-white mr-2'
                    : 'text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 mr-2';

                filterCompleted.className = filter === 'completed'
                    ? 'text-sm px-3 py-1 rounded bg-primary text-white'
                    : 'text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300';
            }
        });
    </script>
<?php endif; ?>
</body>
</html>
