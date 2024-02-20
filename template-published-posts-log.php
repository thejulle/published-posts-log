<?php
/*
Template Name: Published Posts Log
*/

// Redirect user to home page if they are not an administrator
if (!current_user_can('administrator')) :
  wp_redirect(home_url());
  exit;
endif;

get_header();

global $wpdb;
$table_name = $wpdb->prefix . 'published_posts_log';
$data = null;

// get post_id from get parameter and sanitize it
$post_id = filter_input(INPUT_GET, 'post_id', FILTER_SANITIZE_NUMBER_INT);

if ($post_id) :
  // Fetch data from custom database table
  $data = $wpdb->get_row("SELECT * FROM $table_name WHERE post_id = $post_id");
endif;
?>
  <div class="published-posts-log">
    <h1>Published Posts Log</h1>

    <form action="<?= esc_url(get_permalink($post->ID)); ?>" method="GET">
      <label for="published-posts-log__post_id">Select a post by its ID</label>
      <input type="number" name="post_id" id="published-posts-log__post_id">
      <input type="submit" value="Find post">
    </form>

    <?php
      if ($data) :
        ?>
          <table>
            <thead>
              <tr>
                <th>Post ID</th>
                <th>Post Title</th>
                <th>Author</th>
                <th>Published By</th>
                <th>Publish Date</th>
                <th>Categories</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><?= $data->post_id; ?></td>
                <td><a href="<?= esc_url(get_permalink($data->post_id)); ?>"><?= $data->post_title; ?></a></td>
                <td><?= get_the_author_meta('display_name', $data->post_author); ?></td>
                <td><?= get_the_author_meta('display_name', $data->published_by); ?></td>
                <td><?= $data->publish_date; ?></td>
                <td>
                  <?php
                    $categories = explode(',', $data->categories);
                    if ($categories) :
                      foreach ($categories as $key => $category) :
                        echo get_cat_name($category);

                        if ((count($categories) - 1) > $key) :
                          echo ', ';
                        endif;

                      endforeach;
                    endif;
                  ?>
                </td>
              </tr>
            </tbody>
          </table>
          <p>Original post content:</p>
          <div class="published-posts-log__post-content">
            <?= apply_filters('the_content', $data->post_content); ?>
          </div>
        <?php
      elseif ($post_id) :
        echo "<p>No data found for post ID: $post_id</p>";
      endif;
    ?>
  </div>

<?php
get_footer();
