<?php
// Country name mapping utility
class CountryNameMapper {
    // Comprehensive mappings between SVG names, common variations and database names
    private static $mappings = [
        // United States variations
        'United States of America' => 'United States',
        'USA' => 'United States',
        'US' => 'United States',
        'U.S.A.' => 'United States',
        'U.S.' => 'United States',
        'America' => 'United States',

        // United Kingdom variations
        'United Kingdom' => 'United Kingdom',
        'UK' => 'United Kingdom',
        'Britain' => 'United Kingdom',
        'Great Britain' => 'United Kingdom',
        'England' => 'United Kingdom',

        // Common country variations
        'UK' => 'United Kingdom',
        'UAE' => 'United Arab Emirates',
        'U.A.E.' => 'United Arab Emirates',
        'U. A. E.' => 'United Arab Emirates',
        'United Arab Emirates' => 'UAE',

        'Federation of Russia' => 'Russian Federation',
        'Russia' => 'Russian Federation',
        'Soviet Union' => 'Russian Federation',
        'USSR' => 'Russian Federation',

        'Czech Republic' => 'Czechia',
        'Czechia' => 'Czech Republic',
        'Czech' => 'Czechia',

        'Macedonia' => 'North Macedonia',
        'North Macedonia' => 'Macedonia',
        'FYROM' => 'North Macedonia', // Former Yugoslav Republic of Macedonia

        'The Bahamas' => 'Bahamas',
        'Bahamas' => 'The Bahamas',

        'The Gambia' => 'Gambia',
        'Gambia' => 'The Gambia',

        'Congo, Democratic Republic of the' => 'Democratic Republic of the Congo',
        'Democratic Republic of the Congo' => 'Congo, Democratic Republic of the',
        'Democratic Republic of Congo' => 'Democratic Republic of the Congo',
        'DRC' => 'Democratic Republic of the Congo',
        'Congo-Kinshasa' => 'Democratic Republic of the Congo',

        'Republic of the Congo' => 'Republic of Congo',
        'Republic of Congo' => 'Republic of the Congo',
        'Congo-Brazzaville' => 'Republic of the Congo',
        'Congo' => 'Republic of the Congo',

        'Central African Rep.' => 'Central African Republic',
        'Central African Republic' => 'Central African Rep.',

        'Ivory Coast' => 'Côte d\'Ivoire',
        'Côte d\'Ivoire' => 'Ivory Coast',
        'Cote d\'Ivoire' => 'Ivory Coast',
        'Cote dIvoire' => 'Ivory Coast',

        'South Korea' => 'Korea, Republic of',
        'Korea, Republic of' => 'South Korea',
        'Republic of Korea' => 'South Korea',
        'South Korea' => 'Republic of Korea',

        'North Korea' => 'Korea, Democratic People\'s Republic of',
        'Korea, Democratic People\'s Republic of' => 'North Korea',
        'Democratic People\'s Republic of Korea' => 'North Korea',
        'North Korea' => 'Democratic People\'s Republic of Korea',

        'Taiwan' => 'Taiwan, Province of China',
        'Taiwan, Province of China' => 'Taiwan',
        'Taiwan Province of China' => 'Taiwan',

        'Laos' => 'Lao People\'s Democratic Republic',
        'Lao People\'s Democratic Republic' => 'Laos',
        'Lao PDR' => 'Laos',

        'Burma' => 'Myanmar',
        'Myanmar' => 'Burma',

        'Swaziland' => 'Eswatini',
        'Eswatini' => 'Swaziland',
        'Kingdom of Eswatini' => 'Eswatini',

        'The Netherlands' => 'Netherlands',
        'Netherlands' => 'The Netherlands',
        'Holland' => 'Netherlands',

        'Moldova' => 'Moldova, Republic of',
        'Moldova, Republic of' => 'Moldova',
        'Republic of Moldova' => 'Moldova',

        'Syria' => 'Syrian Arab Republic',
        'Syrian Arab Republic' => 'Syria',

        'Iran' => 'Iran, Islamic Republic of',
        'Iran, Islamic Republic of' => 'Iran',
        'Islamic Republic of Iran' => 'Iran',

        'Vietnam' => 'Viet Nam',
        'Viet Nam' => 'Vietnam',

        'Tanzania' => 'Tanzania, United Republic of',
        'Tanzania, United Republic of' => 'Tanzania',
        'United Republic of Tanzania' => 'Tanzania',

        'Venezuela' => 'Venezuela, Bolivarian Republic of',
        'Venezuela, Bolivarian Republic of' => 'Venezuela',
        'Bolivarian Republic of Venezuela' => 'Venezuela',

        'Bolivia' => 'Bolivia, Plurinational State of',
        'Bolivia, Plurinational State of' => 'Bolivia',
        'Plurinational State of Bolivia' => 'Bolivia',

        'Brunei' => 'Brunei Darussalam',
        'Brunei Darussalam' => 'Brunei',

        'Cape Verde' => 'Cabo Verde',
        'Cabo Verde' => 'Cape Verde',

        'East Timor' => 'Timor-Leste',
        'Timor-Leste' => 'East Timor',

        'Macedonia' => 'North Macedonia',
        'North Macedonia' => 'Macedonia',

        'Macao' => 'Macao, Special Administrative Region of China',
        'Macao, Special Administrative Region of China' => 'Macao',

        // Countries from the globe SVG list
        'Congo, Democratic Republic' => 'Democratic Republic of the Congo',
        'Congo, Republic' => 'Republic of the Congo',
        'Palestinian Territories' => 'Palestine',
        'Palestine' => 'Palestinian Territories',
        'State of Palestine' => 'Palestine',
        'Palestine' => 'Palestine',
        'Palestine, State of' => 'qodes',
        'Holy See' => 'Vatican City',
        'Vatican City' => 'Holy See',
        'Saint Helena' => 'Saint Helena, Ascension and Tristan da Cunha',
        'Ascension and Tristan da Cunha' => 'Saint Helena, Ascension and Tristan da Cunha',
        'British Virgin Islands' => 'Virgin Islands, British',
        'U.S. Virgin Islands' => 'Virgin Islands, U.S.',
        'St. Kitts and Nevis' => 'Saint Kitts and Nevis',
        'St. Vincent and the Grenadines' => 'Saint Vincent and the Grenadines',
        'St. Lucia' => 'Saint Lucia',
        'Ivory Coast' => 'Côte d\'Ivoire',
        'Cote d\'Ivoire' => 'Côte d\'Ivoire',
        'São Tomé and Príncipe' => 'Sao Tome and Principe',
        'Faeroe Islands' => 'Faroe Islands',
        'Falkland Islands' => 'Falkland Islands (Malvinas)',
        'Macao' => 'Macao SAR',
        'Reunion' => 'Réunion',
    ];
    
    public static function normalizeCountryName($name) {
        if (empty($name)) {
            return $name;
        }

        $originalName = trim($name);
        $lowerName = strtolower($originalName);

        // First, check if we have a direct mapping
        foreach (self::$mappings as $svgName => $dbName) {
            if (strtolower($svgName) === $lowerName) {
                return $dbName;
            }
            if (strtolower($dbName) === $lowerName) {
                return $dbName; // Return the standardized name
            }
        }

        // If no direct mapping found, try to normalize using common patterns
        // Remove common extra text that may appear
        $patterns = [
            '/the /i',
            '/republic of /i',
            '/democratic republic of /i',
            '/federal republic of /i',
            '/kingdom of /i',
            '/state of /i',
            '/province of /i',
            '/special administrative region of china/i',
        ];

        $cleanedName = preg_replace($patterns, '', $originalName);

        // Try to match cleaned name
        foreach (self::$mappings as $svgName => $dbName) {
            if (stripos($svgName, $cleanedName) !== false || stripos($cleanedName, $svgName) !== false) {
                return $dbName;
            }
            if (stripos($dbName, $cleanedName) !== false || stripos($cleanedName, $dbName) !== false) {
                return $dbName;
            }
        }

        // Return the original name if no mapping found
        return $originalName;
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
            self::normalizeCountryName($name)  // Normalized version
        ];

        // Add mapped variations
        foreach (self::$mappings as $svgName => $dbName) {
            if (strtolower($svgName) === strtolower($name)) {
                $variations[] = $dbName;
            } elseif (strtolower($dbName) === strtolower($name)) {
                $variations[] = $svgName;
            }
        }

        // Try to find similar names by partial matching
        $lowerName = strtolower($name);
        foreach (self::$mappings as $svgName => $dbName) {
            if (strpos(strtolower($svgName), $lowerName) !== false || strpos($lowerName, strtolower($svgName)) !== false) {
                $variations[] = $dbName;
            }
            if (strpos(strtolower($dbName), $lowerName) !== false || strpos($lowerName, strtolower($dbName)) !== false) {
                $variations[] = $dbName;
                $variations[] = $svgName;
            }
        }

        // Generate common abbreviations and full forms
        $abbreviations = [
            'USA' => 'United States',
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'UAE' => 'United Arab Emirates',
            'DRC' => 'Democratic Republic of the Congo',
            'DR Congo' => 'Democratic Republic of the Congo',
            'Congo-Kinshasa' => 'Democratic Republic of the Congo',
            'Congo-Brazzaville' => 'Republic of the Congo'
        ];

        foreach ($abbreviations as $abbr => $fullName) {
            if (strtolower($name) === strtolower($abbr)) {
                $variations[] = $fullName;
            } elseif (strtolower($name) === strtolower($fullName)) {
                $variations[] = $abbr;
            }
        }

        // Remove duplicates and return
        return array_unique($variations);
    }

    // Method to get standardized country name from the globe list
    public static function getStandardizedName($inputName) {
        // This could be used to map to the exact names from COUNTRIES array
        // For now, we'll return the normalized name
        return self::normalizeCountryName($inputName);
    }
}
?>