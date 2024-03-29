<?php
/**
 * @package   Task Management
 * @author    Ntabethemba Ntshoza
 * @date      11-10-2023
 * @copyright Copyright © 2023 VMP By Maneza
 */
// session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'Task.php'; // Include the Task class
require 'Users.php'; // Include the Task class
// Include the weather integration file
require 'weather.php';

// Define your OpenWeatherMap API key and city
$apiKey = '4e8f3a3d6960a08f787632c2eca2e89f';
$city = 'Cape Town';

$users = new User();


$allUsers = $users->getAllUsers();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve the user ID from your authentication system or form input
    if(isset($_SESSION['user_id'])){
        $userId = $_SESSION['user_id'];
    }
 // Adjust this line to get the user ID from

    // Create a new Task object
    $task = new Task();

    // Set task properties from form input
    $task->setTitle($_POST["title"]);
    $task->setDescription($_POST["description"]);
    $task->setDueDate($_POST["due_date"]);
    $task->setUserId($userId); // Set the user ID

    $task->setCompleted(isset($_POST["completed"]) ? 1 : 0);

    // Insert the task into the database
    $task->save();
    header('Location: viewAllTasks.php');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>

<body>
    <div class="container">

    <?php $weatherData = getCurrentWeather($city, $apiKey); ?>

<!-- Styling for weather information -->
<div style="background-color: #f0f0f0; padding: 20px; text-align: center;">
    <h3>Current Weather</h3>
    <p>City: <?php echo $weatherData["name"]; ?></p>
    <p>Current Temp: <?php echo $weatherData["main"]["temp"]; ?></p>
    <p>Min: <?php echo $weatherData["main"]["temp_min"]; ?></p>
    <p>Max: <?php echo $weatherData["main"]["temp_max"]; ?></p>
    <p>Weather: <?php echo $weatherData["weather"][0]["description"]; ?></p>
</div>

        <h1 class="text-center">Add a New Task</h1>
        <form class="form-horizontal col-md-6 col-md-offset-3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <label for="title" class="col-sm-2 control-label">Title:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
            </div>
            <div class="form-group">
                <label for="description" class="col-sm-2 control-label">Description:</label>
                <div class="col-sm-10">
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
            </div>
            <div class="form-group">
                <label for="due_date" class="col-sm-2 control-label">Due Date:</label>
                <div class="col-sm-10">
                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                </div>
            </div>
            <div class="form-group">
                <label for="completed" class="col-sm-2 control-label">Completed:</label>
                <div class="col-sm-10">
                    <input type="checkbox" id="completed" name="completed">
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary" value="Post">Add Task</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>

</html>