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
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    post_title text NOT NULL,
    post_content longtext NOT NULL,
    post_author varchar(255) NOT NULL,
    published_by varchar(255) NOT NULL,
    publish_date datetime NOT NULL,
    categories text NOT NULL,
    PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'published_posts_log_create_table');
  
// Save various post information to custom table when post is published
function published_posts_log_save_post($ID, $post) {
  if ($post->post_type === 'post' && $post->post_status === 'publish') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'published_posts_log';

    // Check if the post has been processed before
    $processed_before = get_post_meta($ID, '_published_posts_log_processed', true);

    if ($processed_before) :
      return;
    else :
      $post_id = $ID;
      $post_title = $post->post_title;
      $post_content = $post->post_content;
      $post_author = $post->post_author;
      $published_by = $post->post_modified_by;
      $publish_date = $post->post_date;
      $categories = implode(',', wp_get_post_categories($post_id));
      
      $wpdb->insert(
        $table_name,
        [
          'post_id' => $post_id,
          'post_title' => $post_title,
          'post_content' => $post_content,
          'post_author' => $post_author,
          'published_by' => $published_by,
          'publish_date' => $publish_date,
          'categories' => $categories,
        ]
      );

      // Set metadata to indicate that the post has been processed
      update_post_meta($ID, '_published_posts_log_processed', true);
    endif;
  }
}
add_action('publish_post', 'published_posts_log_save_post', 10, 2);
    
// Register custom page template
function published_posts_log_register_template($templates) {
  $templates['published-posts-log-template.php'] = 'Published Posts Log';
  return $templates;
}
add_filter('theme_page_templates', 'published_posts_log_register_template');
    
// Load template based on user's choice
function published_posts_log_load_template($template) {
  global $post;
  
  if (!empty($post->post_template) && file_exists(get_template_directory() . '/' . $post->post_template)) {
    return $template;
  }
  
  $template_name = get_post_meta($post->ID, '_wp_page_template', true);
  
  if (!empty($template_name) && 'published-posts-log-template.php' === $template_name) {
    return plugin_dir_path(__FILE__) . 'template-published-posts-log.php';
  }
  
  return $template;
}
add_filter('template_include', 'published_posts_log_load_template');

// Enqueue stylesheet
function published_posts_log_enqueue_styles() {
  wp_enqueue_style('published-posts-log-style', plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'published_posts_log_enqueue_styles');