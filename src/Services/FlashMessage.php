<?php

namespace App\Services;

/**
 * Flash Message Service - Handle session-based flash messages
 * Provides consistent API for setting and retrieving flash messages with automatic cleanup
 */
class FlashMessage
{
    private const FLASH_KEY = '_flash';
    private const OLD_INPUT_KEY = '_old_input';

    /**
     * Set a flash message
     * @param string $type Message type (error, success, info, warning)
     * @param string|array $message Message string or array of messages
     */
    public function set(string $type, string|array $message): void
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }

        if (is_array($message)) {
            $_SESSION[self::FLASH_KEY][$type] = array_merge($_SESSION[self::FLASH_KEY][$type] ?? [], $message);
        } else {
            $_SESSION[self::FLASH_KEY][$type][] = $message;
        }
    }

    /**
     * Get flash messages for a type and clear them
     * @param string $type Message type
     * @return array Array of messages
     */
    public function get(string $type): array
    {
        if (!isset($_SESSION[self::FLASH_KEY][$type])) {
            return [];
        }

        $messages = $_SESSION[self::FLASH_KEY][$type];
        unset($_SESSION[self::FLASH_KEY][$type]);

        // Clean up empty flash array
        if (empty($_SESSION[self::FLASH_KEY])) {
            unset($_SESSION[self::FLASH_KEY]);
        }

        return $messages;
    }

    /**
     * Check if there are messages for a type
     * @param string $type Message type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$type]) && !empty($_SESSION[self::FLASH_KEY][$type]);
    }

    /**
     * Get all flash messages as an array (for passing to views)
     * @return array Associative array of message types to message arrays
     */
    public function all(): array
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            return [];
        }

        $messages = $_SESSION[self::FLASH_KEY];
        unset($_SESSION[self::FLASH_KEY]);
        return $messages;
    }

    /**
     * Set old input data (for form repopulation)
     * @param array $data
     */
    public function setOld(array $data): void
    {
        $_SESSION[self::OLD_INPUT_KEY] = $data;
    }

    /**
     * Get old input data and clear it
     * @return array
     */
    public function getOld(): array
    {
        $old = $_SESSION[self::OLD_INPUT_KEY] ?? [];
        unset($_SESSION[self::OLD_INPUT_KEY]);
        return $old;
    }

    /**
     * Check if there is old input data
     * @return bool
     */
    public function hasOld(): bool
    {
        return isset($_SESSION[self::OLD_INPUT_KEY]);
    }

    /**
     * Clear all flash messages
     */
    public function clear(): void
    {
        unset($_SESSION[self::FLASH_KEY]);
        unset($_SESSION[self::OLD_INPUT_KEY]);
    }

    /**
     * Convenience method for error messages
     */
    public function error(string|array $message): void
    {
        $this->set('error', $message);
    }

    /**
     * Convenience method for success messages
     */
    public function success(string|array $message): void
    {
        $this->set('success', $message);
    }

    /**
     * Convenience method for info messages
     */
    public function info(string|array $message): void
    {
        $this->set('info', $message);
    }

    /**
     * Convenience method for warning messages
     */
    public function warning(string|array $message): void
    {
        $this->set('warning', $message);
    }
}
