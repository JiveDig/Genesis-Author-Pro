<?php

class Genesis_Author_Pro_Term_Meta {

	/**
	 * The tag object for the term being edited
	 *
	 * @since 1.1
	 *
	 * @var obj
	 * @access public
	 */
	var $tag;


	/**
	 * The taxonomy id for the term being edited
	 *
	 * @since 1.1
	 *
	 * @var string
	 * @access public
	 */
	var $taxonomy;

	/**
	 * Builds the custom sorting options for the term editor.
	 *
	 * @since 1.1
	 *
	 * @access public
	 * @param  string $taxonomy
	 * @return void
	 */
	function __construct( $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Enqueues the required scripts and styles for the term editor.
	 *
	 * @since 1.1
	 *
	 * @access public
	 * @return void
	 */
	function enqueue_scripts() {
		wp_enqueue_script( 'common'   );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox'  );

		wp_enqueue_script( 'genesis_author_pro_term_js'  , GENESIS_AUTHOR_PRO_RESOURCES_URL . 'js/term.js'  , array(), 0.1 );
		wp_enqueue_style(  'genesis_author_pro_term_css' , GENESIS_AUTHOR_PRO_RESOURCES_URL . 'css/term.css', array(), 0.1 );
	}

	/**
	 * Callback on the "{$taxonomy}_edit_form" action.
	 * Builds the settings output for the term options and sets up the metaboxes for sorting.
	 *
	 * @since 1.1
	 *
	 * @access public
	 * @param  obj $tag
	 * @return void
	 */
	function edit_form( $tag ) {

		$this->tag = $tag;

		$books = get_posts( array(
				'post_type' => 'books',
				'tax_query' => array(
					array(
						'taxonomy' => $this->taxonomy,
						'field'    => 'id',
						'terms'    => $this->tag->term_id,
					),
				),
				'posts_per_page' => -1,
			) );

		foreach ( $books as $book ) {
			add_meta_box( $book->post_name, $book->post_title, array( $this, 'book_sortable' ), 'book-sort', 'main', '', $book );
		}

		$display = $this->get_field_value( 'custom-sort' ) ? '' : ' style="display:none;" ';

?>
		<div class="wrap gap-clear">

			<h3><?php _e( 'Book Order', 'genesis-author-pro' ); ?></h3>

			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order' , 'meta-box-order-nonce', false ); ?>

			<table class="form-table">
				<tbody>
				<?php $this->checkbox( 'custom-sort', __( 'Enable custom sort', 'genesis-author-pro' ), __( 'Enabling this option will display a sortable, drag-and-drop container below that includes all books in this term.', 'genesis-author-pro' ), __( 'Custom Book Sort', 'genesis-author-pro' ) ); ?>
				</tbody>
			</table>

			<div id="custom-sort-container"<?php echo $display; ?>>
				<div class="metabox-holder">
					<div class="postbox-container">
						<?php do_meta_boxes( 'book-sort', 'main', '' ); ?>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function ($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('book-sort');

				//adds toggle to show/hide sortable
				$('body').on('change', '#custom-sort', function() {
					$('#custom-sort-container').toggle();
				});


			});
			//]]>
		</script>
		<?php

	}

	/**
	 * Metabox callback.
	 * Builds HTML for the sortable metaboxes that allow book order sorting.
	 *
	 * @since 1.1
	 *
	 * @access public
	 * @param  null   $object
	 * @param  object $book
	 * @return void
	 */
	function book_sortable( $object, $book ) {

		$book = $book['args'];

		if ( $image = genesis_get_image( array( 'post_id' => $book->ID, 'format' => 'url', 'size' => 'author-pro-image' ) ) ) {
			printf( '<div class="author-pro-featured-image"><img src="%s" alt="%s" /></div>', $image, the_title_attribute( array( 'echo' => 0, 'post' => $book->ID ) ) );
		} else {
			printf( '<div class="author-pro-featured-image"><div class="author-pro-placeholder">%s</div></div>', get_the_title( $book ) );
		}

		printf( '<input type="hidden" name="genesis-meta[book-sort][]" value="%s" />', $book->ID );

	}

	/**
	 * Outputs checkbox field.
	 *
	 * @since 1.1
	 *
	 * @param string  $id        ID to use when building  checkbox.
	 * @param string  $name      Label text for the checkbox.
	 *
	 */
	function checkbox( $id, $label, $description = '', $th = '' ) {
		printf( '<tr valign="top"><th scope="row">%5$s</th><td><p><label for="%1$s"><input type="checkbox" name="genesis-meta[%1$s]" id="%1$s" value="1"%2$s />%3$s</label></p>%4$s</td></tr>',
			$id,
			checked( $this->get_field_value( $id ), 1, false ),
			$label,
			empty( $description ) ? '' : sprintf( '<p class="description">%s</p>', $description ),
			$th
		);
		echo '<br />';
	}

	/**
	 * Gets the field value from the current term using provided ID.
	 *
	 * @since 1.1
	 *
	 * @access public
	 * @param  string $id
	 * @param  bool   $single (default: true)
	 * @return void
	 */
	function get_field_value( $id, $single = true ) {

		if ( function_exists( 'get_term_meta' ) ) {
			return get_term_meta( $this->tag->term_id, $id, $single );
		}

		return isset( $this->tag->meta[$id] ) ? $this->tag->meta[$id] : '';

	}

}
