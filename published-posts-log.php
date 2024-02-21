<?php
/*
Plugin Name: Published Posts Log
Description: Logs information about published posts to a custom database table.
Version: 1.0
Author: Juho Järvensivu
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Create custom table on plugin activation
function published_posts_log_create_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'published_posts_log';
  
  $charset_collate = $wpdb->get_charset_collate();
  
  $sql = "CREATE TABLE $table_name (
    id int NOT NULL AUTO_INCREMENT,
    post_id int NOT NULL,
    post_title text NOT NULL,
    post_content longtext NOT NULL,
    post_author int NOT NULL,
    published_by int NOT NULL, 
    publish_date datetime NOT NULL,
    categories varchar(255) NOT NULL,
    PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'published_posts_log_create_table');
  
// Save various post information to custom table after post is saved to database
function published_posts_log_after_insert_post($post_id, $post, $update, $post_before) {

  // Check that post type is post and post status is publish
  if ($post->post_type !== 'post' || $post->post_status !== 'publish') :
    return;
  endif;
  
  global $wpdb;
  $table_name = $wpdb->prefix . 'published_posts_log';

  // Check if the post exists in the table already
  $post_in_table = $wpdb->get_row("SELECT * FROM $table_name WHERE post_id = $post->ID");

  if ($post_in_table) :
    return;
  endif;
  
  // Setup post data in array
  $post_data = [
    'post_id' => $post->ID,
    'post_title' => $post->post_title,
    'post_content' => $post->post_content,
    'post_author' => $post->post_author,
    'published_by' => get_current_user_id(),
    'publish_date' => $post->post_date,
    'categories' => implode(',', wp_get_post_categories($post->ID)),
  ];

  // Define the data formats
  $data_formats = [
    '%d', // post_id (int)
    '%s', // post_title (string)
    '%s', // post_content (string)
    '%d', // post_author (int)
    '%d', // published_by (int)
    '%s', // publish_date (string)
    '%s'  // categories (string)
  ];

  // Insert data into custom table
  $wpdb->insert(
    $table_name,
    $post_data,
    $data_formats
  );

  // Check for errors
  if ($wpdb->last_error) :
    // Handle error
    error_log('Error inserting post data: ' . $wpdb->last_error);
  endif;
}
add_action('wp_after_insert_post', 'published_posts_log_after_insert_post', 10, 4);
    
// Register custom page template
function published_posts_log_register_template($templates) {
  $templates['template-published-posts-log.php'] = 'Published Posts Log';
  return $templates;
}
add_filter('theme_page_templates', 'published_posts_log_register_template');
    
// Define path to template file inside plugin directory
function published_posts_log_load_template($template) {
  if (is_page_template('template-published-posts-log.php')) {
    $template = plugin_dir_path(__FILE__) . 'template-published-posts-log.php';
  }
  return $template;
}
add_filter('page_template', 'published_posts_log_load_template');

// Enqueue stylesheet
function published_posts_log_enqueue_styles() {
  wp_enqueue_style('published-posts-log-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], filemtime(plugin_dir_path(__FILE__) . 'assets/style.css'));
}
add_action('wp_enqueue_scripts', 'published_posts_log_enqueue_styles');