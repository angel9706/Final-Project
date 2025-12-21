<?php

namespace App\Controllers;

use App\Config\Auth;
use App\Config\Response;
use App\Config\EmailNotification;
use App\Models\User;

class AuthController
{
    private $userModel;
    private $emailNotification;

    public function __construct()
    {
        $this->userModel = new User();
        $this->emailNotification = new EmailNotification();
    }

    /**
     * Register new user
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
            Response::validationError([
                'name' => 'Name is required',
                'email' => 'Email is required',
                'password' => 'Password is required'
            ]);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::validationError(['email' => 'Invalid email format']);
        }

        if (strlen($input['password']) < 6) {
            Response::validationError(['password' => 'Password must be at least 6 characters']);
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($input['email'])) {
            Response::validationError(['email' => 'Email already registered']);
        }

        // Create user
        if ($this->userModel->create($input['name'], $input['email'], $input['password'])) {
            $user = $this->userModel->findByEmail($input['email']);
            $token = Auth::generateToken($user['id'], $user['email'], $user['role']);
            
            // Create session for the user
            Auth::login($user['id'], $user['email'], $user['role'], $user['name']);
            
            // Send welcome email (disabled for now to avoid errors)
             $this->emailNotification->sendWelcomeEmail($user['email'], $user['name']);
            
            Response::success([
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'token' => $token
            ], 'Registration successful', 201);
        }

        Response::error('Failed to create user');
    }

    /**
     * Login user
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['email']) || empty($input['password'])) {
            Response::validationError([
                'email' => 'Email is required',
                'password' => 'Password is required'
            ]);
        }

        // Find user
        $user = $this->userModel->findByEmail($input['email']);

        if (!$user || !$this->userModel->verifyPassword($input['password'], $user['password'])) {
            Response::error('Invalid email or password', null, 401);
        }

        // Generate token
        $token = Auth::generateToken($user['id'], $user['email'], $user['role']);

        // Also create session for web clients
        Auth::login($user['id'], $user['email'], $user['role'], $user['name']);

        Response::success([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $token
        ], 'Login successful');
    }

    /**
     * Get current user profile
     */
    public function me()
    {
        $user = Auth::getCurrentUser();

        if (!$user) {
            Response::unauthorized();
        }

        $userData = $this->userModel->findById($user->sub);

        Response::success([
            'id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'created_at' => $userData['created_at']
        ]);
    }

    /**
     * Verify token validity
     */
    public function verify()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();

        if (!$user) {
            Response::unauthorized();
        }

        $userData = $this->userModel->findById($user->sub);

        Response::success([
            'id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'valid' => true
        ], 'Token is valid');
    }

    /**
     * Logout user (client-side only, JWT based)
     */
    public function logout()
    {
        Response::success(null, 'Logout successful');
    }

    /**
     * Update user profile
     */
    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();

        if (!$user) {
            Response::unauthorized();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['name']) || empty($input['email'])) {
            Response::validationError([
                'name' => 'Name is required',
                'email' => 'Email is required'
            ]);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::validationError(['email' => 'Invalid email format']);
        }

        // Check if email is already used by another user
        $existingUser = $this->userModel->findByEmail($input['email']);
        if ($existingUser && $existingUser['id'] !== $user->sub) {
            Response::validationError(['email' => 'Email already in use']);
        }

        // Update user
        if ($this->userModel->update($user->sub, $input['name'], $input['email'])) {
            $updated = $this->userModel->findById($user->sub);
            
            Response::success([
                'id' => $updated['id'],
                'name' => $updated['name'],
                'email' => $updated['email'],
                'role' => $updated['role']
            ], 'Profile updated successfully');
        }

        Response::error('Failed to update profile');
    }
}
