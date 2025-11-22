<?php
// Country name mapping utility
class CountryNameMapper {
    // Common mappings between SVG names and database names
    private static $mappings = [
        // Common variations that might exist
        'United States of America' => 'United States',
        'USA' => 'United States',
        'US' => 'United States',
        'U.S.A.' => 'United States',
        'U.S.' => 'United States',
        
        'United Kingdom' => 'UK',
        'UK' => 'United Kingdom',
        'Britain' => 'United Kingdom',
        'Great Britain' => 'United Kingdom',
        
        'Russian Federation' => 'Russia',
        'USSR' => 'Russia',
        
        'Czech Republic' => 'Czechia',
        'Czechia' => 'Czech Republic',
        
        'Macedonia' => 'North Macedonia',
        'North Macedonia' => 'Macedonia',
        
        // Other potential variations
        'The Bahamas' => 'Bahamas',
        'Bahamas' => 'The Bahamas',
        
        'Republic of the Congo' => 'Congo',
        'Congo' => 'Republic of the Congo',
        
        'Central African Rep.' => 'Central African Republic',
        'Central African Republic' => 'Central African Rep.',

        'Tunisia' => 'tunis',
        'tunis' => 'Tunisia',
        
        // Add more mappings as needed based on your data
    ];
    
    public static function normalizeCountryName($name) {
        if (empty($name)) {
            return $name;
        }
        
        $name = trim($name);
        
        // Check if we have a direct mapping
        foreach (self::$mappings as $svgName => $dbName) {
            if (strtolower($svgName) === strtolower($name)) {
                return $dbName;
            }
        }
        
        // Return the original name if no mapping found
        return $name;
    }
    
    public static function getMappingVariations($name) {
        if (empty($name)) {
            return [];
        }
        
        $variations = [
            $name,  // Original
            trim($name),  // Trimmed
            ucwords(strtolower($name)),  // Proper case
            strtoupper($name),  // Uppercase
            strtolower($name),  // Lowercase
        ];
        
        // Add mapped variations
        foreach (self::$mappings as $svgName => $dbName) {
            if (strtolower($svgName) === strtolower($name)) {
                $variations[] = $dbName;
            } elseif (strtolower($dbName) === strtolower($name)) {
                $variations[] = $svgName;
            }
        }
        
        // Remove duplicates and return
        return array_unique($variations);
    }
}
?>