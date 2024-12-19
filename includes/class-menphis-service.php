<?php
if (!defined('ABSPATH')) exit;

class MenphisService {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_service_meta_boxes'));
        add_action('save_post', array($this, 'save_service_meta'));
    }

    public function add_service_meta_boxes() {
        add_meta_box(
            'menphis_service_products',
            'Productos Asociados',
            array($this, 'render_products_meta_box'),
            'menphis_service'
        );
    }

    public function render_products_meta_box($post) {
        wp_nonce_field('menphis_service_meta', 'menphis_service_meta_nonce');
        $products = get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1
        ));
        
        $selected_products = get_post_meta($post->ID, '_menphis_service_products', true);
        ?>
        <select name="menphis_service_products[]" multiple>
            <?php foreach ($products as $product) : ?>
                <option value="<?php echo $product->ID; ?>" <?php selected(in_array($product->ID, (array)$selected_products), true); ?>>
                    <?php echo $product->post_title; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function save_service_meta($post_id) {
        if (!isset($_POST['menphis_service_meta_nonce']) || 
            !wp_verify_nonce($_POST['menphis_service_meta_nonce'], 'menphis_service_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['menphis_service_products'])) {
            update_post_meta($post_id, '_menphis_service_products', $_POST['menphis_service_products']);
        }
    }
}

new MenphisService(); 