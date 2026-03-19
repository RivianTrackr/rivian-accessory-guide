/* Admin scripts for Rivian Accessory Guide */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Select all checkboxes.
        $('#cb-select-all').on('change', function() {
            $('input[name="post_ids[]"]').prop('checked', this.checked);
        });

        // Confirm bulk delete.
        $('form').on('submit', function() {
            var action = $('select[name="rag_bulk_action"]').val();
            if (action === 'delete') {
                var checked = $('input[name="post_ids[]"]:checked').length;
                if (checked === 0) {
                    alert('No accessories selected.');
                    return false;
                }
                return confirm('Delete ' + checked + ' accessory(ies)? This cannot be undone.');
            }
        });

        // Dismiss custom notices.
        $('.rag-notice-dismiss').on('click', function() {
            $(this).closest('.rag-notice').fadeOut(200, function() {
                $(this).remove();
            });
        });

        // --- WordPress Media Uploader for Featured Image ---
        var mediaFrame;

        $('#rag-select-image').on('click', function(e) {
            e.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Select Featured Image',
                button: { text: 'Use this image' },
                multiple: false,
            });

            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;

                $('#featured_image_id').val(attachment.id);
                $('#rag-image-preview').attr('src', url).show();
                $('#rag-select-image').text('Change Image');
                $('#rag-remove-image').show();
            });

            mediaFrame.open();
        });

        $('#rag-remove-image').on('click', function(e) {
            e.preventDefault();
            $('#featured_image_id').val('');
            $('#rag-image-preview').attr('src', '').hide();
            $('#rag-select-image').text('Select Image');
            $(this).hide();
        });
    });
})(jQuery);
