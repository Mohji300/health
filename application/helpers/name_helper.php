<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Check if a school name has proper capitalization
 * Proper format: "Abaca Elementary School" not "ABACA ELEMENTARY SCHOOL" or "abaca elementary school"
 */
function is_properly_capitalized($name) {
    // Check if all caps
    if (strtoupper($name) === $name) {
        return false;
    }
    
    // Check if all lowercase
    if (strtolower($name) === $name) {
        return false;
    }
    
    // Check for common capitalization patterns
    $words = explode(' ', $name);
    
    foreach ($words as $word) {
        // Skip very short words like "and", "the", "of", "in", etc.
        if (in_array(strtolower($word), ['and', 'the', 'of', 'in', 'for', 'to', 'with', 'at', 'by'])) {
            // These should be lowercase unless they're the first word
            continue;
        }
        
        // Check if first character is uppercase
        if (!ctype_upper(substr($word, 0, 1))) {
            return false;
        }
        
        // Check if rest of characters are lowercase (except for acronyms like "SHS")
        $rest = substr($word, 1);
        if (strtolower($rest) !== $rest && !preg_match('/^[A-Z]+$/', $word)) {
            // If not all uppercase (acronym) and not all lowercase after first char
            return false;
        }
    }
    
    return true;
}

/**
 * Fix improperly capitalized school names
 * Converts "ABACA ELEMENTARY SCHOOL" to "Abaca Elementary School"
 */
function fix_school_name_capitalization($name) {
    // Common school suffixes
    $suffixes = [
        'ELEMENTARY SCHOOL' => 'Elementary School',
        'ELEMENTARY' => 'Elementary',
        'HIGH SCHOOL' => 'High School',
        'NATIONAL HIGH SCHOOL' => 'National High School',
        'INTEGRATED SCHOOL' => 'Integrated School',
        'SHS' => 'SHS',
        'SENIOR HIGH SCHOOL' => 'Senior High School',
        'SCHOOL' => 'School',
        'ACADEMY' => 'Academy',
        'UNIVERSITY' => 'University',
        'COLLEGE' => 'College',
        'INSTITUTE' => 'Institute'
    ];
    
    // Convert to lowercase first
    $lower_name = strtolower($name);
    
    // Handle common prefixes and special cases
    $words = explode(' ', $lower_name);
    
    // Capitalize each word properly
    $proper_words = array();
    foreach ($words as $index => $word) {
        // Skip empty words
        if (empty($word)) continue;
        
        // Check if this word is part of a known suffix
        $found_suffix = false;
        foreach ($suffixes as $upper_suffix => $proper_suffix) {
            $suffix_words = explode(' ', strtolower($upper_suffix));
            $suffix_length = count($suffix_words);
            
            // Check if the next few words match this suffix
            $check_words = array_slice($words, $index, $suffix_length);
            if ($check_words === $suffix_words) {
                // Found a suffix, use the proper format
                $proper_words[] = $proper_suffix;
                
                // Skip the remaining words of this suffix
                for ($i = 1; $i < $suffix_length; $i++) {
                    array_shift($words);
                }
                $found_suffix = true;
                break;
            }
        }
        
        if (!$found_suffix) {
            // Capitalize first letter, lowercase the rest
            $proper_words[] = ucfirst($word);
        }
    }
    
    $fixed_name = implode(' ', $proper_words);
    
    // Special handling for Roman numerals
    $fixed_name = preg_replace_callback('/\b([ivxlcdm]+)\b/i', function($matches) {
        return strtoupper($matches[1]);
    }, $fixed_name);
    
    // Special handling for acronyms like SHS, STEM, etc.
    $acronyms = ['SHS', 'STEM', 'TVL', 'GAS', 'HUMMS', 'ABM', 'ICT', 'HE'];
    foreach ($acronyms as $acronym) {
        $fixed_name = preg_replace('/\b' . preg_quote(strtolower($acronym), '/') . '\b/i', $acronym, $fixed_name);
    }
    
    return $fixed_name;
}

/**
 * Validate school name format
 * Returns array with validation results
 */
function validate_school_name($name) {
    $result = array(
        'is_valid' => true,
        'message' => '',
        'suggested_name' => '',
        'issues' => array()
    );
    
    // Check if empty
    if (empty(trim($name))) {
        $result['is_valid'] = false;
        $result['message'] = 'School name cannot be empty';
        return $result;
    }
    
    // Check length
    if (strlen($name) > 255) {
        $result['is_valid'] = false;
        $result['issues'][] = 'Name is too long (max 255 characters)';
    }
    
    // Check for proper capitalization
    if (!is_properly_capitalized($name)) {
        $result['is_valid'] = false;
        $result['issues'][] = 'Name should have proper capitalization (e.g., "Abaca Elementary School")';
        $result['suggested_name'] = fix_school_name_capitalization($name);
    }
    
    // Check for all caps
    if (strtoupper($name) === $name && strlen($name) > 3) {
        $result['is_valid'] = false;
        $result['issues'][] = 'Name should not be in ALL CAPS';
        if (empty($result['suggested_name'])) {
            $result['suggested_name'] = fix_school_name_capitalization($name);
        }
    }
    
    // Check for all lowercase
    if (strtolower($name) === $name && strlen($name) > 3) {
        $result['is_valid'] = false;
        $result['issues'][] = 'Name should not be all lowercase';
        if (empty($result['suggested_name'])) {
            $result['suggested_name'] = fix_school_name_capitalization($name);
        }
    }
    
    // Check for multiple spaces
    if (preg_match('/\s{2,}/', $name)) {
        $result['is_valid'] = false;
        $result['issues'][] = 'Name has multiple consecutive spaces';
    }
    
    // Check for special characters (allow only letters, numbers, spaces, and common punctuation)
    if (preg_match('/[^\w\s\-\'\.(),]/u', $name)) {
        $result['is_valid'] = false;
        $result['issues'][] = 'Name contains invalid characters';
    }
    
    // Set message if there are issues
    if (!empty($result['issues'])) {
        $result['message'] = implode(', ', $result['issues']);
    }
    
    return $result;
}

/**
 * Check if a name looks like it might be a person's name (for school head validation)
 */
function looks_like_person_name($name) {
    $name = trim($name);
    
    // Person names typically have 2-4 words
    $words = explode(' ', $name);
    if (count($words) < 2 || count($words) > 4) {
        return false;
    }
    
    // Check for titles
    $titles = ['Dr.', 'Mr.', 'Mrs.', 'Ms.', 'Prof.', 'Sir', 'Madam', 'Hon.'];
    foreach ($titles as $title) {
        if (stripos($name, $title) === 0) {
            return true;
        }
    }
    
    // Check for common name patterns
    $last_word = end($words);
    
    // Common Filipino surnames
    $common_surnames = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 
        'Torres', 'Dela Cruz', 'Gonzales', 'Ramos', 'Aquino', 'Mendoza',
        'Lopez', 'Fernandez', 'Gomez', 'Perez', 'Martinez', 'Rodriguez'
    ];
    
    if (in_array($last_word, $common_surnames)) {
        return true;
    }
    
    // Check if first word looks like a first name
    $first_word = $words[0];
    if (ctype_upper(substr($first_word, 0, 1)) && 
        strtolower(substr($first_word, 1)) === substr($first_word, 1)) {
        return true;
    }
    
    return false;
}