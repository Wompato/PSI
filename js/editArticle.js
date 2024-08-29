function getGravityFormId() {
    // Get the Gravity Forms wrapper element
    const gfWrapper = document.querySelector('.gform_wrapper');

    // Extract the form ID from the wrapper element
    if (gfWrapper) {
        return gfWrapper.id.split('_')[2]; // Splits "gform_wrapper_X" and returns the form ID "X"
    }

    return null;
}

gformId = getGravityFormId();

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
                    'target': '_blank',
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

const previousSelections = new Set();

window.gform.addFilter('gpadvs_settings', function(settings, gpadvs, selectNamespace) {
    if(gpadvs.fieldId == '16') {

        const articleSelect = document.querySelector(`#input_${gformId}_16`);

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

        settings.onInitialize = function () {
            const articleSelect = document.querySelector(`#field_${gformId}_16 .ts-control`);
            articleSelect.children.forEach(function(el) {
                if(el.dataset.value){
                    previousSelections.add(el.dataset.value);
                    performAjaxRequest(el.dataset.value)
                }
            })
        }

        articleSelect.addEventListener('change', function() {
            previousSelections.clear();
            jQuery('.project-list').empty();
        })
         
    }
    if(gpadvs.fieldId == '10'){
        settings.onItemAdd = function(value, item) {
            this.setTextboxValue('');
			this.refreshOptions();
        };
    }
    
    return settings;
});


const additionalImagesPreviewer = document.querySelectorAll('.additional-image-previewer');
const additionalImagesFup = document.querySelectorAll('.additional-image-fup');

additionalImagesPreviewer.forEach( (previewer, index) => {
    const deleteBtn = previewer.querySelector('button');
    deleteBtn.addEventListener('click', function(e) {
        e.preventDefault();

        let val = document.querySelector(`.featured-preview-value-${index} input`);
        val.value = '';
        
        let inputField = previewer.querySelector('.fup-text-input');
        inputField.value = '';
        
        let event = new Event('change', { bubbles: true });
        val.dispatchEvent(event);
    });
})

const featuredImageFUP = document.querySelector('.featured-image-fup');
const imagePreviewer = document.querySelector('.featured-image-previewer');

const imagePreviewerDeleteBtn = imagePreviewer.querySelector('button');
imagePreviewerDeleteBtn.addEventListener('click', function(e) {
    e.preventDefault();
    
    let val = document.querySelector('.featured-preview-value input');
    val.value = '';

    let inputField = imagePreviewer.querySelector('.fup-text-input');
    inputField.value = '';

    let event = new Event('change', { bubbles: true });
    val.dispatchEvent(event);
});

window.addEventListener('DOMContentLoaded', function() {

    const allFupEls = document.querySelectorAll('.ginput_container_fileupload');

    allFupEls.forEach( function (el, index) {
        el.appendChild(createCaptionEl(index));
    })

})

function createCaptionEl(id) {
    let inputTextElement = document.createElement('input');
    inputTextElement.type = 'text';
    inputTextElement.id = 'caption_' + id;
    inputTextElement.name = 'caption_' + id;
    inputTextElement.className = 'fup-caption';
    inputTextElement.placeholder = 'Enter caption';

    inputTextElement.style.marginTop = '10px';
    inputTextElement.style.minWidth = '100%';

    return inputTextElement;
}