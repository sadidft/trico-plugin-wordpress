<?php
/**
 * Trico Theme Functions
 * Minimal block-ready theme for Trico AI Assistant
 * 
 * @package Trico_Theme
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

define('TRICO_THEME_VERSION', '1.0.0');
define('TRICO_THEME_DIR', get_template_directory());
define('TRICO_THEME_URI', get_template_directory_uri());

/**
 * Theme Setup
 */
function trico_theme_setup() {
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('automatic-feed-links');
    add_theme_support('appearance-tools');
    add_theme_support('border');
    add_theme_support('link-color');
    add_theme_support('custom-spacing');
    
    add_editor_style('assets/css/trico-blocks.css');
}
add_action('after_setup_theme', 'trico_theme_setup');

/**
 * Enqueue Scripts & Styles
 */
function trico_theme_assets() {
    wp_enqueue_style(
        'trico-theme-style',
        get_stylesheet_uri(),
        array(),
        TRICO_THEME_VERSION
    );
    
    wp_enqueue_style(
        'trico-blocks',
        TRICO_THEME_URI . '/assets/css/trico-blocks.css',
        array(),
        TRICO_THEME_VERSION
    );
    
    wp_enqueue_style(
        'trico-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap',
        array(),
        null
    );
    
    wp_enqueue_style(
        'aos-css',
        'https://unpkg.com/aos@2.3.1/dist/aos.css',
        array(),
        '2.3.1'
    );
    
    wp_enqueue_script(
        'aos-js',
        'https://unpkg.com/aos@2.3.1/dist/aos.js',
        array(),
        '2.3.1',
        true
    );
    
    wp_enqueue_script(
        'trico-animations',
        TRICO_THEME_URI . '/assets/js/trico-animations.js',
        array('aos-js'),
        TRICO_THEME_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'trico_theme_assets');

/**
 * Register Block Patterns Category
 */
function trico_register_pattern_category() {
    register_block_pattern_category('trico', array(
        'label' => __('Trico Designs', 'trico-theme')
    ));
}
add_action('init', 'trico_register_pattern_category');

/**
 * Clean Output - Remove unnecessary WordPress stuff
 */
function trico_clean_head() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    add_filter('the_generator', '__return_empty_string');
}
add_action('after_setup_theme', 'trico_clean_head');

/**
 * Add custom body classes
 */
function trico_body_classes($classes) {
    $classes[] = 'trico-theme';
    
    if (is_singular()) {
        $classes[] = 'trico-singular';
    }
    
    return $classes;
}
add_filter('body_class', 'trico_body_classes');

/**
 * Allow additional MIME types for upload
 */
function trico_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter('upload_mimes', 'trico_mime_types');