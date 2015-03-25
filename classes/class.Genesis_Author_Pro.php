<?php

/**
 * Genesis_Author_Pro class.
 * This class has static methods that conditionally load other objects to do the main work.
 */
class Genesis_Author_Pro {

	/**
	 * Action on the load-post.php and load-post-new.php hooks.
	 * Checks to make sure the current post type is books
	 * then instantiates the Genesis_Author_Pro_Book_Meta object.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static public function maybe_do_book_meta() {

		global $typenow, $Genesis_Author_Pro_CPT, $Genesis_Author_Pro_Book_Meta;

		if( $Genesis_Author_Pro_CPT->post_type == $typenow ) {

			$Genesis_Author_Pro_Book_Meta = new Genesis_Author_Pro_Book_Meta;

		}

	}

	/**
	 * Action on the save_post hook.
	 * Checks to make sure the _genesis_author_pro_nonce is set and correctly verified
	 * then instantiates the Genesis_Author_Pro_Save object.
	 *
	 * @access public
	 * @static
	 * @param mixed $post_id
	 * @param mixed $post
	 * @return void
	 */
	static public function maybe_do_save( $post_id, $post ) {

		if ( isset( $_POST['_genesis_author_pro_nonce'] ) && wp_verify_nonce( $_POST['_genesis_author_pro_nonce'], GENESIS_AUTHOR_PRO_CLASSES_DIR ) ) {

			/* Get the post type object. */
			$post_type = get_post_type_object( $post->post_type );

			/* Check if the current user has permission to edit the post. */
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ){
				return;
			}

			new Genesis_Author_Pro_Save( $post_id, $post );

		}

	}

	/**
	 * Filter our bulk updated/trashed messages so that it uses "book(s)" rather than "post".
	 *
	 * @access public
	 * @static
	 * @param array $bulk_messages
	 * @param array $bulk_counts
	 * @return void
	 */
	static public function bulk_updated_messages( $bulk_messages, $bulk_counts ) {

		global $Genesis_Author_Pro_CPT;

		$bulk_messages[$Genesis_Author_Pro_CPT->post_type] = array(
			'updated'   => _n( '%s book updated.'                            , '%s books updated.'                              , $bulk_counts['updated']  , 'genesis-author-pro'    ),
			'locked'    => _n( '%s book not updated, somebody is editing it.', '%s books not updated, somebody is editing them.', $bulk_counts['locked']   , 'genesis-author-pro'    ),
			'deleted'   => _n( '%s book permanently deleted.'                , '%s books permanently deleted.'                  , $bulk_counts['deleted']  , 'genesis-author-pro'    ),
			'trashed'   => _n( '%s book moved to the Trash.'                 , '%s books moved to the Trash.'                   , $bulk_counts['trashed']  , 'genesis-author-pro'    ),
			'untrashed' => _n( '%s book restored from the Trash.'            , '%s books restored from the Trash.'              , $bulk_counts['untrashed'], 'genesis-author-pro'  ),
		);

		return $bulk_messages;

	}

}
