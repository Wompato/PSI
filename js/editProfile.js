jQuery(document).ready(function($) {
    // Function to handle the click event of the image uploader button
    $('.image-uploader-btn').on('click', function(e) {
        e.preventDefault();

        let hideIfValue = $(this).closest('.hide-if-value');
        let showIfValue = hideIfValue.prev('.show-if-value');
        let hiddenInput = showIfValue.prev();

        // Open media uploader
        let mediaUploader = wp.media({
            title: 'Upload Image',
            button: {
                text: 'Insert Image'
            },
            multiple: false
        });

        // When a file is selected, get the URL and insert it into the page
        mediaUploader.on('select', function() {
            let attachment = mediaUploader.state().get('selection').first().toJSON();
            let imageUrl = attachment.url;
            let attachmentId = attachment.id;

            hideIfValue.hide();
            showIfValue.show();

            hiddenInput.val(attachmentId);

            let imgElement = showIfValue.find('img');
            imgElement.attr('src', imageUrl);
        });

        // Open the media uploader
        mediaUploader.open();
    });

    // Function to handle the click event of the "Edit" button
    $('.custom-edit-metadata').on('click', function(e) {
        e.preventDefault();

        let showIfValue = $(this).closest('.show-if-value');
        let hiddenInput = showIfValue.prev();
        
        // Get the attachment ID of the clicked media item
        let attachmentID = hiddenInput.val();
        
        // Ensure that the attachment ID is available
        if (attachmentID) {
            // Open the attachment details modal for the specified attachment ID
            // Create a media frame for editing media details
            let mediaFrame = wp.media.modal;

            console.log(mediaFrame)

            // Open the media frame
            mediaFrame.open();

            console.log(mediaFrame)


            mediaFrame.on('open', function() {
                // Select the attachment in the media modal
                let selection = uploader.state().get('selection');
                let attachment = wp.media.attachment(attachmentID);
                attachment.fetch();
                selection.add(attachment);
            });

            // Open the media modal
            uploader.open();
        } else {
            console.log('Attachment ID not found.');
        }
    });
});



jQuery(document).ready(function($) {
    $('.custom-remove-image').on('click', function(e) {
        e.preventDefault();

        // Find the closest show-if-value div relative to the clicked button
        let showIfValue = $(this).closest('.show-if-value');

        // Find the corresponding hide-if-value div
        let hideIfValue = showIfValue.next('.hide-if-value');

        // Find the hidden input within the hide-if-value div
        let hiddenInput = showIfValue.prev();

        // Clear the value of the hidden input
        hiddenInput.val('');

        // Clear the src attribute of the img element
        let imgElement = showIfValue.find('img');
        imgElement.attr('src', '');

        // Hide the show-if-value div
        showIfValue.hide();

        // Show the hide-if-value div
        hideIfValue.show();
    });
});
