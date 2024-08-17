jQuery(document).ready(function($) {
    $('#expert-search-form').on('submit', findExpert);
});

function findExpert(e) {
    e.preventDefault();

    jQuery('#search-results').html('');

    let searchText = jQuery('#search-text').val();

    if (!searchText) {
        alert('Please enter some text to search');
        return;
    }

    // Check if the spinner exists and append it if it does not
    if (jQuery('.loading-dual-ring').length === 0) {
        jQuery('#search-results').before('<div class="loading-dual-ring"></div>');
    }

    let searchTerms = parseSearchTerms(searchText);
    let searchLogic = document.querySelector('input[name="logic"]:checked').value;

    jQuery.ajax({
        url: '/wp-json/psi/v1/find-experts',
        method: 'GET',
        data: {
            search_terms: searchTerms,  // Send the processed search terms
            logic: searchLogic          // Include the search logic (AND/OR)
        },
        success: function(response) {
            jQuery('.loading-dual-ring').remove();  // Remove the spinner on success
            if(response.length === 0) {
                jQuery('#search-results').html('<p>No experts found.</p>');
                return;
            }
            response.forEach(user => {
                let output = createExpertHTML(user);
                jQuery('#search-results').append(output);
            });
        },
        error: function(xhr, status, error) {
            jQuery('.loading-dual-ring').remove();
            console.error('API Error:', {
                xhr: xhr,
                status: status,
                error: error
            });
            jQuery('#search-results').html('Error fetching data.');
        }
    });
}


function parseSearchTerms(input) {
    const terms = [];
    const regex = /"([^"]+)"|([^",\s][^,]*)/g;
    let match;

    while ((match = regex.exec(input)) !== null) {
        if (match[1]) {
            // Exact match (quoted term)
            terms.push({ value: match[1].trim(), type: 'exact' });
        } else if (match[2]) {
            // Substring match (unquoted term)
            terms.push({ value: match[2].trim(), type: 'substring' });
        }
    }

    console.log(terms);
    return terms;
}

function createExpertHTML(user) {
    // Example function to create HTML for each user
    let userElement = jQuery('<div class="user-profile"></div>');
    let displayNameElement = jQuery('<p></p>').text(user.display_name + ' - ');
    let profileLink = jQuery('<a></a>').attr('href', user.permalink).text('Profile');
    displayNameElement.append(profileLink);
    userElement.append(displayNameElement);
    return userElement;
}
