<?php

/**
 * @package   Task Management
 * @author    Ntabethemba Ntshoza
 * @date      11-10-2023
 * @copyright Copyright © 2023 VMP By Maneza
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'Connection.php'; // Include the database connection file

global $db;

class User
{

    private $name;
    private $email;
    private $password;
    private $id;

    // Constructor
    //   public function __construct()
    //   {
    //       // Initialize any necessary properties
    //   }

    // Getter method for name
    public function getUserId()
    {
        return $this->id;
    }

    // Setter method for name
    public function setUserId($id)
    {
        $this->id = $id;
    }

    // Getter method for name
    public function getName()
    {
        return $this->name;
    }

    // Setter method for name
    public function setName($name)
    {
        $this->name = $name;
    }

    // Getter method for email
    public function getEmail()
    {
        return $this->email;
    }

    // Setter method for email
    public function setEmail($email)
    {
        $this->email = $email;
    }

    // Getter method for password
    public function getPassword()
    {
        return $this->password;
    }

    // Setter method for password
    public function setPassword($password)
    {
        $this->password = $password;
    }

    // Function to save a new user to the database
    public function save()
    {
        global $db; // Use the database connection from connect file

        // Hash the password before saving
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

        // Prepare the SQL statement
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";

        // Bind parameters and execute the query
        $stmt = $db->prepare($sql);
        $stmt->bind_param("sss", $this->name, $this->email, $hashedPassword);

        try {
            // Your registration code here
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $this->getUserId();
                $_SESSION['registration_success'] = 'User registered successfully!';

                return true; // User saved successfully
            } else {
                return false; // User could not be saved
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION['registration_error'] = 'An error occurred while registering. This email address is already in use.';
        }
    }


    /**
     * Login funtion
     *
     * @param String $email
     * @param String $password
     * @return bool
     */
    public function login($email, $password)
    {
        global $db; // Use the database connection from Connection.php

        // Prepare the SQL statement to retrieve user data based on the provided email
        $sql = "SELECT id, name, password FROM users WHERE email = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->error) {
            die("Database query error: " . $stmt->error);
        }

        $result = $stmt->get_result();

        // Check if a user with the provided email exists
        if ($result->num_rows === 0) {
            return false; // User not found
        }

        // Fetch the user data
        $user = $result->fetch_assoc();

        // Compare the provided password with the stored hashed password
        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION['login_success'] = 'Logged in successfully!';

            return true; // Password is correct
        } else {
            return false; // Password is incorrect
        }
    }

    /**
     * Validate Password
     * @param string $password
     * 
     * @return mixed
     */
    function validatePassword($password)
    {
        // Check password length
        if (strlen($password) < 8) {
            return false;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for at least one digit
        if (!preg_match('/\d/', $password)) {
            return false;
        }

        // Password passed all checks
        return true;
    }

    /**
     * Forgot password function
     *
     * @param string $email
     * @return Bool
     */
    public function forgotPassword($email)
    {
        global $db;
        // Check if the email exists in the database
        if ($this->emailExists($email)) {
            // Generate a reset token and set it in the database
            $resetToken = bin2hex(random_bytes(16)); // Generate a random token
            $resetExpiration = date('Y-m-d H:i:s', strtotime('+1 hour')); // Set expiration time

            // Update the user's reset_token and reset_expiration in the database
            $sql = "UPDATE users SET reset_token = ?, reset_expiration = ? WHERE email = ?";
            // $stmt = $this->conn->prepare($sql);
            $stmt = $db->prepare($sql);
            $stmt->bind_param('sss', $resetToken, $resetExpiration, $email);

            if ($stmt->execute()) {
                // Send a reset email to the user with a link to reset their password
                $resetLink = "http://ekomi.local/task-dashboard/resetPassword.php?email=" . $email . "&token=" . $resetToken; //edit this to your website
                $message = "To reset your password, click on the following link:\n" . "<a href='{$resetLink}'>Reset your password now</a>";
                $_SESSION['reset_password'] =  $message;

                return true;
            }
        }

        return false;
    }


    private function emailExists($email)
    {
        global $db; // Use the database connection from connect.php

        // Prepare the SQL statement to fetch all users
        $sql = "SELECT * FROM users";

        // Execute the query
        $result = $db->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($email == $row['email']) {
                    return true;
                }
            }

            $_SESSION['wrong_email'] =  "{$email} does not exist...";
            // The loop has finished checking all users, and the email doesn't exist
            return false;
        }
    }

    public function isValidPasswordResetRequest($email, $token) {
        global $db; // Use the database connection from connect.php or your configuration file.

        // Prepare a SQL statement to check if the email and token match a valid reset request.
        $sql = "SELECT email FROM users WHERE email = ? AND reset_token = ? AND reset_expiration > NOW()";

        // Use prepared statements to prevent SQL injection.
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $stmt->store_result();
        
        // If a row is found, the request is valid.
        $isValid = ($stmt->num_rows === 1);

        $stmt->close();

        return $isValid;
    }

    /**
     * Update Password function
     *
     * @param string $email
     * @param string $newPassword
     * @return void
     */
    public function updatePassword($email, $newPassword) {
        global $db; // Use the database connection from connect.php or your configuration file.

        // Hash the new password (you should use a secure hashing method).
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Prepare an SQL statement to update the user's password.
        $sql = "UPDATE users SET password = ? WHERE email = ?";

        // Use prepared statements to prevent SQL injection.
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $hashedPassword, $email);
        $result = $stmt->execute();
        $stmt->close();

        // Return true if the update was successful, false otherwise.
        return $result;
    }

    /**
     * Get All users
     *
     * @return array
     */
    public static function getAllUsers()
    {
        global $db; // Use the database connection from connect.php

        // Prepare the SQL statement to fetch all users
        $sql = "SELECT * FROM users";

        // Execute the query
        $result = $db->query($sql);

        // Check if the query was successful
        if ($result) {
            $users = [];

            // Fetch user data and create User objects
            while ($row = $result->fetch_assoc()) {
                $user = new User();
                $user->setUserId($row['id']);
                $user->setName($row['name']);
                $user->setEmail($row['email']);
                // Add more setters for other user properties as needed

                // Add the User object to the array
                $users[] = $user;
            }

            return $users;
        } else {
            return false; // Query failed
        }
    }

    /**
     * Get a user by ID
     *
     * @param string|int $user_id
     * @return array
     */
    public function getUserById($user_id)
    {
        global $db; // Use the database connection from Connection.php

        // Prepare the SQL statement to retrieve user data by user_id
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            // Execute the query
            $result = $stmt->get_result();

            // Check if a user with the provided user_id exists
            if ($result->num_rows === 1) {
                // Fetch user data
                $userData = $result->fetch_assoc();
                return $userData;
            }
        }

        return null; // User not found or query failed
    }


    /**
     * Function to update an existing user in the database
     *
     * @return Bool
     */
    public function update()
    {
        global $db; // Use the database connection from connect.php

        // Prepare the SQL statement
        $sql = "UPDATE tasks 
                SET title = ?, description = ?, due_date = ?, user_id = ?, completed = ? 
                WHERE id = ?";

        // Bind parameters and execute the query
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            "sssiii",
            $this->title,
            $this->description,
            $this->dueDate,
            $this->userId,
            $this->completed,
            $this->id
        );

        if ($stmt->execute()) {
            return true; // Task updated successfully
        } else {
            return false; // Task could not be updated
        }
    }

    /**
     * Delete users
     *
     * @return bool
     */
    public function delete()
    {
        global $db; // Use the database connection from connect.php

        // Prepare the SQL statement
        $sql = "DELETE FROM users WHERE id = ?";

        // Bind parameters and execute the query
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $this->id);

        if ($stmt->execute()) {
            return true; // Task deleted successfully
        } else {
            return false; // Task could not be deleted
        }
    }
}
