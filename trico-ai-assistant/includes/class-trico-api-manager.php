<?php
/**
 * Trico API Manager
 * Handles Groq API key rotation (machine gun style)
 * 
 * @package Trico_AI_Assistant
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Trico_API_Manager {
    
    private $keys = array();
    private $current_index = 0;
    private $max_keys = 15;
    private $rate_limits = array();
    
    public function __construct() {
        $this->load_keys();
        $this->load_rate_limits();
    }
    
    private function load_keys() {
        for ($i = 1; $i <= $this->max_keys; $i++) {
            $key = getenv('GROQ_KEY_' . $i);
            if (!empty($key)) {
                $this->keys[] = array(
                    'index' => $i,
                    'key' => $key,
                    'requests_today' => 0,
                    'last_used' => null,
                    'is_limited' => false,
                    'limited_until' => null
                );
            }
        }
    }
    
    private function load_rate_limits() {
        $limits = get_option('trico_api_rate_limits', array());
        
        foreach ($this->keys as &$key) {
            if (isset($limits[$key['index']])) {
                $key = array_merge($key, $limits[$key['index']]);
                
                // Reset daily counter jika sudah ganti hari
                if (isset($key['last_date']) && $key['last_date'] !== date('Y-m-d')) {
                    $key['requests_today'] = 0;
                }
                
                // Reset rate limit jika sudah lewat waktu
                if ($key['is_limited'] && !empty($key['limited_until'])) {
                    if (time() > strtotime($key['limited_until'])) {
                        $key['is_limited'] = false;
                        $key['limited_until'] = null;
                    }
                }
            }
        }
    }
    
    private function save_rate_limits() {
        $limits = array();
        
        foreach ($this->keys as $key) {
            $limits[$key['index']] = array(
                'requests_today' => $key['requests_today'],
                'last_used' => $key['last_used'],
                'last_date' => date('Y-m-d'),
                'is_limited' => $key['is_limited'],
                'limited_until' => $key['limited_until']
            );
        }
        
        update_option('trico_api_rate_limits', $limits);
    }
    
    public function get_key_count() {
        return count($this->keys);
    }
    
    public function get_available_key_count() {
        $count = 0;
        foreach ($this->keys as $key) {
            if (!$key['is_limited']) {
                $count++;
            }
        }
        return $count;
    }
    
    public function get_next_key() {
        if (empty($this->keys)) {
            return new WP_Error(
                'no_api_keys',
                __('No Groq API keys configured. Add GROQ_KEY_1 to HF Secrets.', 'trico-ai')
            );
        }
        
        $attempts = 0;
        $max_attempts = count($this->keys);
        
        while ($attempts < $max_attempts) {
            $this->current_index = ($this->current_index + 1) % count($this->keys);
            $key = &$this->keys[$this->current_index];
            
            // Skip jika key sedang rate limited
            if ($key['is_limited']) {
                $attempts++;
                continue;
            }
            
            // Update usage
            $key['last_used'] = current_time('mysql');
            $key['requests_today']++;
            
            $this->save_rate_limits();
            
            return $key['key'];
        }
        
        // Semua key rate limited
        return new WP_Error(
            'all_keys_limited',
            __('All API keys are rate limited. Please wait before trying again.', 'trico-ai')
        );
    }
    
    public function mark_key_limited($key_value, $retry_after = 60) {
        foreach ($this->keys as &$key) {
            if ($key['key'] === $key_value) {
                $key['is_limited'] = true;
                $key['limited_until'] = date('Y-m-d H:i:s', time() + $retry_after);
                $this->save_rate_limits();
                return true;
            }
        }
        return false;
    }
    
    public function reset_key_limit($key_index) {
        foreach ($this->keys as &$key) {
            if ($key['index'] === $key_index) {
                $key['is_limited'] = false;
                $key['limited_until'] = null;
                $key['requests_today'] = 0;
                $this->save_rate_limits();
                return true;
            }
        }
        return false;
    }
    
    public function get_keys_status() {
        $status = array();
        
        foreach ($this->keys as $key) {
            $status[] = array(
                'index' => $key['index'],
                'name' => 'GROQ_KEY_' . $key['index'],
                'key_preview' => substr($key['key'], 0, 8) . '...' . substr($key['key'], -4),
                'requests_today' => $key['requests_today'],
                'last_used' => $key['last_used'],
                'is_limited' => $key['is_limited'],
                'limited_until' => $key['limited_until'],
                'status' => $key['is_limited'] ? 'limited' : 'active'
            );
        }
        
        return $status;
    }
    
    public function call_groq($messages, $model = null, $options = array()) {
        $api_key = $this->get_next_key();
        
        if (is_wp_error($api_key)) {
            return $api_key;
        }
        
        if (is_null($model)) {
            $model = trico()->core->get_model('balanced');
        }
        
        $defaults = array(
            'temperature' => 0.7,
            'max_tokens' => 8192,
            'top_p' => 1,
            'stream' => false
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $body = array(
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'],
            'max_tokens' => $options['max_tokens'],
            'top_p' => $options['top_p'],
            'stream' => $options['stream']
        );
        
        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'timeout' => 120,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        ));
        
        if (is_wp_error($response)) {
            trico()->core->log('Groq API Error: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Handle rate limit
        if ($code === 429) {
            $retry_after = 60;
            $headers = wp_remote_retrieve_headers($response);
            if (!empty($headers['retry-after'])) {
                $retry_after = intval($headers['retry-after']);
            }
            
            $this->mark_key_limited($api_key, $retry_after);
            
            trico()->core->log('Groq rate limited, retrying with next key...', 'warning');
            
            // Retry dengan key lain
            return $this->call_groq($messages, $model, $options);
        }
        
        if ($code !== 200) {
            $error_message = isset($data['error']['message']) 
                ? $data['error']['message'] 
                : 'Unknown API error';
            
            trico()->core->log('Groq API Error (' . $code . '): ' . $error_message, 'error');
            
            return new WP_Error('groq_api_error', $error_message, array('code' => $code));
        }
        
        return $data;
    }
    
    public function get_usage_stats() {
        $total_requests = 0;
        $active_keys = 0;
        $limited_keys = 0;
        
        foreach ($this->keys as $key) {
            $total_requests += $key['requests_today'];
            if ($key['is_limited']) {
                $limited_keys++;
            } else {
                $active_keys++;
            }
        }
        
        return array(
            'total_keys' => count($this->keys),
            'active_keys' => $active_keys,
            'limited_keys' => $limited_keys,
            'requests_today' => $total_requests,
            'current_index' => $this->current_index
        );
    }
}