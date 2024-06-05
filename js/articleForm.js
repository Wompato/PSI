function performAjaxRequest(dataValue) {
    // Perform AJAX request only if the item is a user selection
    

        // Check if the loading spinner already exists
        if (jQuery('.loading-dual-ring').length === 0) {
            jQuery('.project-list').append('<div class="loading-dual-ring"></div>');
        }

        jQuery.ajax({
            url: '/wp-json/psi/v1/project/' + dataValue,
            method: 'GET',
            success: function(response) {
                // Extract project details from response
                const projectId = response.post_id || 'N/A';
                const projectName = response.post_title || 'N/A';

                const projectLink = response.post_permalink;

                let fundingAgency;
                let fundingProgram;

                if(response.funding_agency_data && response.funding_agency_data.length > 0) {
                    if(response.funding_agency_data[0].nickname) {
                        fundingAgency = response.funding_agency_data[0].nickname;
                    } else {
                        fundingAgency = response.funding_agency_data[0].name;
                    }
                    
                } else {
                    fundingAgency = 'N/A';
                }

                if(response.funding_program_data && response.funding_program_data.length > 0) {
                    if( response.funding_program_data[0].nickname) {
                        fundingProgram =  response.funding_program_data[0].nickname;
                    } else {
                        fundingProgram =  response.funding_program_data[0].name;
                    }
                    
                } else {
                    fundingProgram = 'N/A';
                }
    
                // Create a <p> tag with the text content
                const paragraph = jQuery('<p>').text(projectName + ' - ' + fundingAgency + ', ' + fundingProgram);

                // Create a link element
                const link = jQuery('<a>').attr({
                    'href': projectLink,
                    'target': '_blank', // Open link in a new tab
                    'data-id': projectId
                }).text('View Project');

              
                const icon = jQuery('<i>').attr({
                    'class': 'wpmi__icon wpmi__label-0 wpmi__position-after wpmi__align-middle wpmi__size-1 fa fa-external-link',
                    'style': 'font-size: 1em;'
                });

                // Append the icon inside the link
                link.append(icon);

                // Append <p> tag and link to project list
                const container = jQuery('<div>').attr({
                    'data-id': projectId
                }).addClass('project-item');;

                container.append(paragraph, link);
                jQuery('.project-list').append(container);

                jQuery('.loading-dual-ring').remove();

            },
            error: function(xhr, status, error) {
                
                console.error('Error fetching project details:', error);

                jQuery('.loading-dual-ring').remove();
            }
        });
    
}

// Keep track of previously selected items
const previousSelections = new Set();

window.gform.addFilter('gpadvs_settings', function(settings, gpadvs, selectNamespace) {
    if(gpadvs.fieldId == '32') {

        // Add event listener for item_add event
        settings.onItemAdd = function(value, item) {
            this.setTextboxValue('');
			this.refreshOptions();

            if (previousSelections.has(value)) {
                return;
            }  
            previousSelections.add(value)
            performAjaxRequest(value);

        };

        settings.onDelete = function(value, item) {
            if(!value) {
                return;
            }
            let v = value[0];
            if (!previousSelections.has(v)) {
                return;
            }  
            previousSelections.delete(v)
            jQuery('.project-list [data-id="' + v + '"]').remove();
        }
         
    }
    if(gpadvs.fieldId == '23'){
        settings.onItemAdd = function(value, item) {
            this.setTextboxValue('');
            this.refreshOptions();

        };
    }
    // Return modified settings object
    return settings;
});


jQuery(document).ready(function($) {
    let multUpFields = $('.gform_fileupload_multifile');

    multUpFields.each(function(index) {
        let el = createCaptionEl(index);
        $(this).append(el);
    });

});

function createCaptionEl(index) {
    let inputTextElement = jQuery('<input>', {
        type: 'text',
        id: 'captionInput' + index,
        name: 'captionInput' + index,
        placeholder: 'Enter caption' // Optionally, display the index in the placeholder
        // value: 'Default Value'
    });

    inputTextElement.css('margin-top', '10px'); // Use jQuery for consistency

    return inputTextElement;
}

// Wait for the DOM to be ready
document.addEventListener('DOMContentLoaded', function() {

    // Wait for TinyMCE to be initialized
    tinymce.on('addeditor', function(event) {
        var editor = event.editor;

        

        // Register paste event listener
        editor.on('paste', function(e) {
           e.preventDefault();
           // Get the clipboard data
           var clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;
           if (clipboardData) {
               // Retrieve HTML content from clipboard
               var pastedHTML = clipboardData.getData('text/html');

               pastedHTML = pastedHTML.replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '<br>');

               
               // Insert the processed HTML content into the editor
               editor.setContent(pastedHTML);
           }
        });

        
    });
});


