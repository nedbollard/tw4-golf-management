<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Models\Staff;
use App\Repositories\StaffRepository;
use App\Services\Logger;
use App\Utility\NameHelper;

/**
 * Staff Controller - Admin-only staff management
 */
class StaffController extends BaseController
{
    private Logger $logger;
    private StaffRepository $staffRepository;

    public function __construct(Application $app, Logger $logger, StaffRepository $staffRepository)
    {
        parent::__construct($app);
        $this->logger = $logger;
        $this->staffRepository = $staffRepository;
    }

    public function index(): void
    {
        $this->requireRole('admin');
        
        $staff = $this->staffRepository->findAll();
        
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
            $existingStaff = $this->staffRepository->findByUsername((string) ($data['username'] ?? ''));
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
            
            try {
                $staffId = $this->staffRepository->save($newStaff, (string) ($_SESSION['username'] ?? 'system'));
            } catch (\RuntimeException $e) {
                if (!$this->isDuplicateUsernameError($e)) {
                    throw $e;
                }

                $this->flash->error(['username' => 'Username already exists.']);
                $this->flash->setOld($data);
                $this->redirect('/staff');
                return;
            }
            
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

    public function edit(int $id): void
    {
        $this->requireRole('admin');
        
        // Convert string ID to integer
        $staffId = (int)$id;
        
        $staff = $this->staffRepository->findById($staffId);
        if (!$staff) {
            $this->flash->error('Staff member not found.');
            $this->redirect('/staff');
            return;
        }
        
        $this->render('staff/edit', [
            'staff' => $staff,
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole('admin');
        $staffId = (int)$id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrf()) {
                $this->flash->error('Invalid CSRF token');
                $this->redirect("/staff/edit/{$staffId}");
                return;
            }

            $data = $this->getPostData();
            $errors = [];
            
            // Validate required fields
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            }
            
            if (empty($data['role'])) {
                $errors['role'] = 'Role is required';
            }

            if (!in_array($data['is_active'] ?? null, ['0', '1', 0, 1], true)) {
                $errors['is_active'] = 'Status must be active or inactive';
            }
            
            // Check if username already exists (excluding current staff)
            $existingStaff = $this->staffRepository->findByUsername((string) ($data['username'] ?? ''));
            if ($existingStaff && $existingStaff->getStaffId() != $staffId) {
                $errors['username'] = 'Username already exists';
            }
            
            if (!empty($errors)) {
                $this->flash->error($errors);
                $this->flash->setOld($data);
                $this->redirect("/staff/edit/{$staffId}");
                return;
            }
            
            // Load existing staff member
            $staff = $this->staffRepository->findById($staffId);
            if (!$staff) {
                $this->flash->error('Staff member not found.');
                $this->redirect('/staff');
                return;
            }

            if ($staff->getUsername() === ($_SESSION['username'] ?? '') && (string) $data['is_active'] === '0') {
                $this->flash->error(['is_active' => 'You cannot deactivate your own account.']);
                $this->flash->setOld($data);
                $this->redirect("/staff/edit/{$staffId}");
                return;
            }
            
            // Update staff member
            $staff->setUsername($data['username']);
            $staff->setRole($data['role']);
            $staff->setIsActive((string) $data['is_active'] === '1');
            
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
            
            try {
                $this->staffRepository->save($staff, (string) ($_SESSION['username'] ?? 'system'));
            } catch (\RuntimeException $e) {
                if (!$this->isDuplicateUsernameError($e)) {
                    throw $e;
                }

                $this->flash->error(['username' => 'Username already exists.']);
                $this->flash->setOld($data);
                $this->redirect("/staff/edit/{$staffId}");
                return;
            }
            $success = true;
            
            if ($success) {
                $this->logger->logConfig('staff_updated', [
                    'row_id' => $staff->getStaffId(),
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

    public function delete(int $id): void
    {
        $this->requireRole('admin');
        
        // Convert string ID to integer
        $staffId = (int)$id;
        
        $staff = $this->staffRepository->findById($staffId);
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
        $staff->deactivate();
        $this->staffRepository->save($staff, (string) ($_SESSION['username'] ?? 'system'));
        $success = true;
        
        if ($success) {
            $this->logger->logConfig('staff_deleted', [
                'row_id' => $staffId,
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

    private function isDuplicateUsernameError(\Throwable $exception): bool
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if (!$current instanceof \PDOException) {
                continue;
            }

            $driverCode = $current->errorInfo[1] ?? null;
            $message = strtolower($current->getMessage());
            if ($driverCode === 1062 || (
                (string) $current->getCode() === '23000'
                && str_contains($message, 'username')
                && (str_contains($message, 'duplicate') || str_contains($message, 'unique'))
            )) {
                return true;
            }
        }

        return false;
    }
}
