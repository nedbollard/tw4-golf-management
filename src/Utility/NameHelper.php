<?php

namespace App\Utility;

class NameHelper
{
    /**
     * Capitalize a name properly with special handling for various naming conventions
     * 
     * @param string $name The name to capitalize
     * @return string The properly capitalized name
     */
    public static function capitalizeName(string $name): string
    {
        if (empty($name)) {
            return $name;
        }
        
        // Convert to lowercase first to handle mixed input
        $name = strtolower(trim($name));
        
        // Handle special cases
        $name = self::handleSpecialCases($name);
        
        // Handle Samoan names and other multi-word names
        $name = self::handleMultiWordNames($name);
        
        // Default capitalization for remaining cases
        return self::defaultCapitalization($name);
    }
    
    /**
     * Handle special cases like O'Connor, McDonald, MacDonald
     */
    private static function handleSpecialCases(string $name): string
    {
        // Handle O'Connor, O'Neill, etc.
        if (preg_match("/^o'[a-z]/i", $name)) {
            return preg_replace("/^o'([a-z])/i", "O'$1", $name);
        }
        
        // Handle Mc/Mac prefixes
        if (preg_match("/^mc([a-z])/i", $name)) {
            return preg_replace("/^mc([a-z])/i", "Mc$1", $name);
        }
        
        if (preg_match("/^mac([a-z])/i", $name)) {
            return preg_replace("/^mac([a-z])/i", "Mac$1", $name);
        }
        
        return $name;
    }
    
    /**
     * Handle multi-word names and Samoan names
     */
    private static function handleMultiWordNames(string $name): string
    {
        // Common Samoan name patterns and prefixes
        $samoanPrefixes = [
            'fa', 'fe', 'fu', 'lu', 'ma', 'mo', 'mu', 'ni', 'pa', 'sa', 'se', 'ta', 'ti', 'to', 'tu', 'ul', 'va'
        ];
        
        $words = explode(' ', $name);
        $result = [];
        
        foreach ($words as $word) {
            // Check for Samoan prefixes
            $lowerWord = strtolower($word);
            foreach ($samoanPrefixes as $prefix) {
                if (str_starts_with($lowerWord, $prefix . '-')) {
                    // Capitalize prefix and the rest
                    $word = ucfirst($prefix) . '-' . ucfirst(substr($word, strlen($prefix) + 1));
                    break;
                }
            }
            
            // Default capitalization for this word
            if (strlen($word) === strlen($lowerWord)) {
                $word = self::defaultCapitalization($word);
            }
            
            $result[] = $word;
        }
        
        return implode(' ', $result);
    }
    
    /**
     * Default capitalization for regular names
     */
    private static function defaultCapitalization(string $name): string
    {
        // Handle hyphenated names
        if (strpos($name, '-') !== false) {
            $parts = explode('-', $name);
            $capitalizedParts = array_map(function($part) {
                return ucfirst(strtolower($part));
            }, $parts);
            return implode('-', $capitalizedParts);
        }
        
        // Handle apostrophe names (general case)
        if (strpos($name, "'") !== false) {
            $parts = explode("'", $name);
            if (count($parts) === 2) {
                return ucfirst(strtolower($parts[0])) . "'" . ucfirst(strtolower($parts[1]));
            }
        }
        
        // Default case
        return ucfirst(strtolower($name));
    }
}
