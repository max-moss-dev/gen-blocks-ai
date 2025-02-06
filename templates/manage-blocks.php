<?php
if (!\defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class GENB_Blocks_List_Table extends WP_List_Table {
    private $_storage;

    public function __construct($storage) {
        parent::__construct([
            'singular' => 'block',
            'plural'   => 'blocks',
            'ajax'     => false
        ]);
        $this->_storage = $storage;
    }

    public function get_columns() {
        return [
            'title' => 'Block Title',
            'description' => 'Description',
            'category' => 'Category'
        ];
    }

    public function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $this->_storage->getBlocks();
    }

    public function column_title($item) {
        $actions = [
            'edit' => sprintf(
                '<a href="%s">Edit</a>',
                admin_url('admin.php?page=gen-blocks-edit&block=' . urlencode($item->name))
            ),
            'delete' => sprintf(
                '<a href="#" class="delete-block" data-name="%s" data-nonce="%s">Delete</a>',
                esc_attr($item->name),
                wp_create_nonce('genb_manage_blocks')
            ),
        ];

        return sprintf('%1$s %2$s', esc_html($item->title), $this->row_actions($actions));
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'description':
                return esc_html($item->description ?: 'No description');
            case 'category':
                return esc_html(ucfirst(str_replace('-', ' ', $item->category ?: 'gen-blocks')));
            default:
                return esc_html($item->$column_name);
        }
    }
}

$nonce = wp_create_nonce('genb_manage_blocks');
$blocks_table = new GENB_Blocks_List_Table($this->_storage);
$blocks_table->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Gen Blocks</h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gen-blocks-generate')); ?>" class="page-title-action">Add New Block</a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Block saved successfully!</p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($blocks_table->items)): ?>
        <p>No blocks have been generated yet.</p>
    <?php else: ?>
        <?php $blocks_table->display(); ?>

        <script>
        jQuery(document).ready(function($) {
            $('.delete-block').on('click', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this block?')) {
                    return;
                }

                const link = $(this);
                const blockName = link.data('name');
                const nonce = link.data('nonce');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'genb_delete_block',
                        block_name: blockName,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            link.closest('tr').fadeOut(400, function() {
                                $(this).remove();
                                if ($('.wp-list-table tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert('Error deleting block: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
    <?php endif; ?>
</div>