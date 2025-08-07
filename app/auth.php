<?php
require_once __DIR__.'/../config/database.php';

class Auth {
    public static function login($email, $password) {
        try {
            $db = FirestoreDB::getInstance();
            $usersRef = $db->collection('users');
            
            $query = $usersRef->where('email', '=', strtolower($email));
            $snapshot = $query->documents();
            
            if ($snapshot->isEmpty()) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            foreach ($snapshot as $doc) {
                $user = $doc->data();
                
                if (!password_verify($password, $user['password_hash'])) {
                    return ['success' => false, 'error' => 'Invalid password'];
                }
                
                // Update last login
                $doc->reference->update([['path' => 'last_login', 'value' => new DateTime()]]);
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $doc->id(),
                        'email' => $user['email'],
                        'name' => $user['name'] ?? '',
                        'role' => $user['role'] ?? 'user'
                    ]
                ];
            }
        } catch (Exception $e) {
            error_log('Login Error: '.$e->getMessage());
            return ['success' => false, 'error' => 'Login failed'];
        }
    }
}