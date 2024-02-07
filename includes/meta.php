<?php
/**
 * Add custom taxonomy meta for property areas
 * 
 * $mingle_status_id = get_term_meta( $area_object->term_id, 'mingle_status_id', true );
 *
 * @param mixed $taxonomy
 * @return string
 */
function mingle_add_status_field( $taxonomy ) {
    ?>
    <div class="form-field term-group">
        <label>Forum Status</label>
        <label for="mingle_status_id">
            <input type="checkbox" name="mingle_status_id" id="mingle_status_id" value="1">
            Lock Forum
        </label>
    </div>
    <?php
}

add_action( 'forum_add_form_fields', 'mingle_add_status_field', 10, 2 );

function mingle_edit_status_field( $term, $taxonomy ) {
    $mingle_status_id = get_term_meta( $term->term_id, 'mingle_status_id', true );
    ?>

    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="mingle_status_id">Forum Status</label></th>
        <td>
            <label for="mingle_status_id">
                <input type="checkbox" name="mingle_status_id" id="mingle_status_id" value="1" <?php checked( 1, $mingle_status_id ); ?>>
                Lock Forum
            </label>
        </td>
    </tr>
    <?php
}

add_action( 'forum_edit_form_fields', 'mingle_edit_status_field', 10, 2 );

function mingle_save_status_meta( $term_id, $tt_id ) {
    if ( (int) $_POST['mingle_status_id'] > 0 && '' !== $_POST['mingle_status_id'] ) {
        $block_id = (int) $_POST['mingle_status_id'];

        add_term_meta( $term_id, 'mingle_status_id', $block_id, true );
    }
}

add_action( 'created_forum', 'mingle_save_status_meta', 10, 2 );

function mingle_update_status_meta( $term_id, $tt_id ) {
    $block_id = (int) $_POST['mingle_status_id'];

    update_term_meta( $term_id, 'mingle_status_id', $block_id );
}

add_action( 'edited_forum', 'mingle_update_status_meta', 10, 2 );

