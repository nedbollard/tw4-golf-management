<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Database;
use App\Models\Staff;
use App\Services\Logger;
use App\Utility\NameHelper;

/**
 * Staff Controller - Admin-only staff management
 */
class StaffController extends BaseController
{
    private Logger $logger;

    public function __construct(Application $app, Logger $logger)
    {
        parent::__construct($app);
        $this->logger = $logger;
    }

    public function index(): void
    {
        $this->requireRole('admin');
        
        $staff = Staff::findAll($this->app->getDatabase());
        
        $this->render('staff/index', [
            'staff' => $staff,
        ]);
    }

    public function add(): void
    {
        $this->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrf()) {
                $this->flash->error('Invalid CSRF token');
                $this->flash->setOld($this->getPostData());
                $this->redirect('/staff');
                return;
            }

            $data = $this->getPostData();
            $errors = [];
            
            // Validate required fields
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            }
            
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            }
            
            if (empty($data['role'])) {
                $errors['role'] = 'Role is required';
            }
            
            // Check if username already exists
            $existingStaff = Staff::findByUsername($this->app->getDatabase(), $data['username']);
            if ($existingStaff) {
                $errors['username'] = 'Username already exists';
            }
            
            if (!empty($errors)) {
                $this->flash->error($errors);
                $this->flash->setOld($data);
                $this->redirect('/staff');
                return;
            }
            
            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Capitalize names properly
            $firstName = NameHelper::capitalizeName($data['first_name'] ?? $data['username']);
            $lastName = NameHelper::capitalizeName($data['last_name'] ?? '');
            
            // Create new staff member
            $newStaff = new Staff(
                $data['username'],
                $passwordHash,
                $firstName,
                $lastName,
                $data['role'],
                true,
                null
            );
            
            $staffId = $newStaff->save($this->app->getDatabase());
            
            if ($staffId) {
                $this->logger->logConfig('staff_added', [
                    'row_id' => $staffId,
                    'username' => $data['username'],
                    'role' => $data['role']
                ], $_SESSION['username'] ?? null);
                
                $this->flash->success("Staff member '{$data['username']}' added successfully.");
            } else {
                $this->flash->error('Failed to add staff member.');
            }
            
            $this->redirect('/staff');
        }
        
        $this->render('staff/add');
    }

    public function edit($id): void
    {
        $this->requireRole('admin');
        
        // Convert string ID to integer
        $rowId = (int)$id;
        
        $staff = Staff::findById($this->app->getDatabase(), $rowId);
        if (!$staff) {
            $this->flash->error('Staff member not found.');
            $this->redirect('/staff');
            return;
        }
        
        $this->render('staff/edit', [
            'staff' => $staff,
        ]);
    }

    public function update($id): void
    {
        $this->requireRole('admin');
        $rowId = (int)$id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrf()) {
                $this->flash->error('Invalid CSRF token');
                $this->redirect("/staff/edit/{$rowId}");
                return;
            }

            $data = $this->getPostData();
            $errors = [];
            
            // Convert string ID to integer
            $staffId = (int)$id;
            
            // Validate required fields
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            }
            
            if (empty($data['role'])) {
                $errors['role'] = 'Role is required';
            }
            
            // Check if username already exists (excluding current staff)
            $existingStaff = Staff::findByUsername($this->app->getDatabase(), $data['username']);
            if ($existingStaff && $existingStaff->getRowId() != $staffId) {
                $errors['username'] = 'Username already exists';
            }
            
            if (!empty($errors)) {
                $this->flash->error($errors);
                $this->flash->setOld($data);
                $this->redirect("/staff/edit/{$rowId}");
                return;
            }
            
            // Load existing staff member
            $staff = Staff::findById($this->app->getDatabase(), $staffId);
            if (!$staff) {
                $this->flash->error('Staff member not found.');
                $this->redirect('/staff');
                return;
            }
            
            // Update staff member
            $staff->setUsername($data['username']);
            $staff->setRole($data['role']);
            
            // Update first and last name if provided
            if (!empty($data['first_name'])) {
                $staff->setFirstName(NameHelper::capitalizeName($data['first_name']));
            }
            if (!empty($data['last_name'])) {
                $staff->setLastName(NameHelper::capitalizeName($data['last_name']));
            }
            
            // Update password if provided
            if (!empty($data['password'])) {
                $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
                $staff->setPasswordHash($passwordHash);
            }
            
            $success = $staff->save($this->app->getDatabase());
            
            if ($success) {
                $this->logger->logConfig('staff_updated', [
                    'row_id' => $staff->getRowId(),
                    'username' => $data['username'],
                    'role' => $data['role'],
                    'password_changed' => !empty($data['password'])
                ], $_SESSION['username'] ?? null);
                
                $this->flash->success("Staff member '{$data['username']}' updated successfully.");
            } else {
                $this->flash->error('Failed to update staff member.');
            }
            
            $this->redirect('/staff');
        }
    }

    public function delete($id): void
    {
        $this->requireRole('admin');
        
        // Convert string ID to integer
        $rowId = (int)$id;
        
        $staff = Staff::findById($this->app->getDatabase(), $rowId);
        if (!$staff) {
            $this->flash->error('Staff member not found.');
            $this->redirect('/staff');
            return;
        }
        
        // Prevent deletion of self
        if ($staff->getUsername() === ($_SESSION['username'] ?? '')) {
            $this->flash->error('You cannot delete your own account.');
            $this->redirect('/staff');
            return;
        }
        
        // Logical deletion - mark as inactive
        $success = $staff->deactivate($this->app->getDatabase());
        
        if ($success) {
            $this->logger->logConfig('staff_deleted', [
                'row_id' => $rowId,
                'username' => $staff->getUsername(),
                'role' => $staff->getRole(),
                'first_name' => $staff->getFirstName(),
                'last_name' => $staff->getLastName()
            ], $_SESSION['username'] ?? null);
            
            $this->flash->success("Staff member '{$staff->getUsername()}' deleted successfully (retained for audit).");
        } else {
            $this->flash->error('Failed to delete staff member.');
        }
        
        $this->redirect('/staff');
    }
}
