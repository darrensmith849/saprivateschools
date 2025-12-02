<?php
if ( empty( $attachments_data ) || ( ! $attachment_switch ) ) {
    return;
}

$attachment_open_on_new_tab = ( $attachment_new_tab ) ? 'target="_blank"' : '';
$attachment_label           = strlen( $attachment_label ) > 0 ? esc_attr( $attachment_label ) : __( "Attachments", "betterdocs-pro" );
echo '<div class="betterdocs-attachment-wrapper">';
echo '<p class="betterdocs-attachment-heading">' . $attachment_label . '</p>';

echo '<div class="attachment-list">';
foreach ( $attachments_data as $data ) {
    $data = json_decode( $data );
    if ( ! property_exists( $data, 'contentType' ) ) {
        $icon_placeholder = isset( $icon_obj[$data->subtype]['icon_svg'] ) ? $icon_obj[$data->subtype]['icon_svg'] : ( isset( $icon_obj[$data->type]['icon_svg'] ) ? $icon_obj[$data->type]['icon_svg'] : '' );
        $img_placeholder  = isset( $icon_obj[$data->subtype]['payload'] ) ? $icon_obj[$data->subtype]['payload'] : ( isset( $icon_obj[$data->type]['payload'] ) ? $icon_obj[$data->type]['payload'] : '' );
        echo '<div class="attachment-details">';
        echo ( count( $img_placeholder ) > 0 && $show_attachment_icon ) ? '<img class="icon-image" src="' . $img_placeholder['url'] . '">' : ( $show_attachment_icon ? '<div class="icon-wrapper">' . $icon_placeholder . '</div>' : '' );
        echo '<a href="' . wp_get_attachment_url( $data->id ) . '"' . $attachment_open_on_new_tab . '/><p class="attachment-name">' . ( strlen( $attachment_default_name ) > 0 ? esc_attr( $attachment_default_name ) : $data->name ) . '</p>' . ( $show_attachment_size ? '<p class="attachment-size">' . $data->filesizeHumanReadable . '</p>' : '' ) . '</a>';
        echo '</div>';
    } else {
        $url_string_name = str_contains( $data->fileurl, 'https://www' ) ? str_replace( 'https://www.', '', $data->fileurl ) : ( str_contains( $data->fileurl, 'http://www' ) ? str_replace( 'http://www', '', $data->fileurl ) : ( str_contains( $data->fileurl, 'https://' ) ? str_replace( 'https://', '', $data->fileurl ) : ( str_contains( $data->fileurl, 'http://' ) ? str_replace( 'http://', '', $data->fileurl ) : str_replace('www.', '',  $data->fileurl) ) ) );
        echo '<div class="attachment-details">';
        echo $show_attachment_icon ? '<div class="icon-wrapper">' . $icon_obj['external']['icon_svg'] . '</div>' : '';
        echo '<a href="' . esc_url( $data->fileurl ) . '"' . $attachment_open_on_new_tab . '/>' . ( strlen( $data->filename ) > 0 ? '<p class="attachment-name">' . esc_attr( $data->filename ) . '</p>' : '<p class="attachment-name">' . esc_attr( $url_string_name ) . '</p>' ) . (  ( strlen( $data->filesize ) > 0 && $show_attachment_size ) ? '<p class="attachment-size">' . esc_attr( $data->filesize ) . '</p>' : '' ) . '</a>';
        echo '</div>';
    }
}
echo '</div>';
echo '</div>';
