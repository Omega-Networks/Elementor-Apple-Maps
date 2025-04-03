<?php
/**
 * JWT Token Generator for Apple MapKit JS
 * 
 * This class handles secure generation of JWT tokens for Apple MapKit JS authentication.
 * Follows the same approach as the Block plugin implementation.
 */
class Elementor_Apple_Maps_JWT {
    
    /**
     * Generate a JWT token for MapKit JS
     * 
     * @param string $key_id     The Key ID from Apple Developer account
     * @param string $team_id    The Team ID from Apple Developer account
     * @param string $private_key The private key content
     * @param int    $expire_time Time in seconds until the token expires (default: 15 minutes)
     * 
     * @return string|WP_Error The JWT token or WP_Error on failure
     */
    public static function generate_token($key_id, $team_id, $private_key, $expire_time = 900) {
		error_log('Generating JWT - Key ID: ' . $key_id . ', Team ID: ' . $team_id . ', Expire Time: ' . $expire_time);
		// Create header
        $header = [
            'alg' => 'ES256',
            'kid' => $key_id,
            'typ' => 'JWT'
        ];
        
        // Create payload
        $payload = [
            'iss' => $team_id,
            'iat' => time(),
            'exp' => time() + $expire_time,
            'origin' => self::get_allowed_origins()
        ];
        
        // exlude the origin restriction from the JWT for local environemts
        // this is to allow tools like wp-env or browsersync to work since the url
        // is not the same as the site url.
        if (wp_get_environment_type() === 'local') {
            unset($payload['origin']);
        }
        
        // Encode header and payload
        $encoded_header = self::base64url_encode(json_encode($header));
        $encoded_payload = self::base64url_encode(json_encode($payload));
        
        // Create signature
        $data = $encoded_header . '.' . $encoded_payload;
        $signature = '';
        
        // Clean up private key - ensure it's properly formatted
        $private_key = self::format_private_key($private_key);
        
        // Create signature using OpenSSL
        $success = openssl_sign($data, $signature, $private_key, OPENSSL_ALGO_SHA256);
        
        if (!$success) {
            $error_message = openssl_error_string();
            error_log('Failed to create signature for MapKit JS token: ' . $error_message);
            return new WP_Error('signature_error', 'Failed to create signature: ' . $error_message);
        }
        
        // Encode signature and create token
        $encoded_signature = self::base64url_encode($signature);
        
        $token = $encoded_header . '.' . $encoded_payload . '.' . $encoded_signature;
    	error_log('Generated JWT: ' . $token);
    	return $token;
    }
    
    /**
     * Validate MapKit JS credentials by attempting to generate a test token
     * 
     * @param string $key_id     The Key ID from Apple Developer account
     * @param string $team_id    The Team ID from Apple Developer account
     * @param string $private_key The private key content
     * 
     * @return true|WP_Error True on success or WP_Error on failure
     */
    public static function validate_credentials($key_id, $team_id, $private_key) {
        error_log('Validating MapKit JS credentials - Key ID: ' . $key_id . ', Team ID: ' . $team_id);
        
        // Validate inputs
        if (empty($key_id)) {
            error_log('MapKit JS validation failed: Empty Key ID');
            return new WP_Error('invalid_key_id', 'Key ID cannot be empty');
        }
        
        if (empty($team_id)) {
            error_log('MapKit JS validation failed: Empty Team ID');
            return new WP_Error('invalid_team_id', 'Team ID cannot be empty');
        }
        
        if (empty($private_key)) {
            error_log('MapKit JS validation failed: Empty Private Key');
            return new WP_Error('invalid_private_key', 'Private Key cannot be empty');
        }
        
        // Validate Key ID format (typically 10 characters)
        if (strlen($key_id) < 5 || strlen($key_id) > 15) {
            error_log('MapKit JS validation failed: Key ID appears to be invalid (unusual length)');
            return new WP_Error('invalid_key_id_format', 'Key ID appears to be invalid. It should be approximately 10 characters');
        }
        
        // Validate Team ID format (typically 10 characters)
        if (strlen($team_id) < 5 || strlen($team_id) > 15) {
            error_log('MapKit JS validation failed: Team ID appears to be invalid (unusual length)');
            return new WP_Error('invalid_team_id_format', 'Team ID appears to be invalid. It should be approximately 10 characters');
        }
        
        // Check if the private key contains the necessary markers
        if (strpos($private_key, 'PRIVATE KEY') === false) {
            error_log('MapKit JS validation failed: Private key does not contain the expected format');
            return new WP_Error('invalid_private_key_format', 'Private Key appears to be invalid. Make sure it includes "-----BEGIN PRIVATE KEY-----" and "-----END PRIVATE KEY-----"');
        }

        try {
            // Attempt to generate a test token with a short expiration time (60 seconds)
            $token = self::generate_token($key_id, $team_id, $private_key, 60);
            
            // Check if we received a WP_Error
            if (is_wp_error($token)) {
                error_log('MapKit JS validation failed: ' . $token->get_error_message());
                return $token; // Return the error
            }
            
            // Check if the token has the expected format (3 parts separated by dots)
            $token_parts = explode('.', $token);
            if (count($token_parts) !== 3) {
                error_log('MapKit JS validation failed: Generated token has an invalid format');
                return new WP_Error('invalid_token_format', 'Generated token has an invalid format');
            }
            
            error_log('MapKit JS credentials validated successfully');
            return true;
        } catch (Exception $e) {
            error_log('MapKit JS validation failed with exception: ' . $e->getMessage());
            return new WP_Error('validation_exception', 'Failed to validate credentials: ' . $e->getMessage());
        }
    }
    
    /**
     * Format the private key for use with OpenSSL
     * 
     * @param string $private_key The private key
     * @return string Formatted private key
     */
    private static function format_private_key($private_key) {
        // Ensure the key has proper PEM formatting
        if (strpos($private_key, '-----BEGIN PRIVATE KEY-----') === false) {
            $private_key = "-----BEGIN PRIVATE KEY-----\n" . 
                           wordwrap($private_key, 64, "\n", true) . 
                           "\n-----END PRIVATE KEY-----";
        }
        
        return $private_key;
    }
    
    /**
     * Get allowed origins for the MapKit JS token
     * 
     * @return string|array Allowed origin(s)
     */
	private static function get_allowed_origins() {
	    $origin = sprintf('%1$s://%2$s', parse_url(home_url(), PHP_URL_SCHEME) ?: 'http', parse_url(home_url(), PHP_URL_HOST));
	    error_log('MapKit JWT Origin: ' . $origin);
	    return $origin;
	}
    
    /**
     * Base64URL encode (JWT specific encoding)
     * 
     * @param string $data Data to encode
     * @return string Base64URL encoded string
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}