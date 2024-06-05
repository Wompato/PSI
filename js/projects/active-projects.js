jQuery(document).ready(function($) {
    const projectListTitle = $('.active-projects h4');

    $('.agency-list').on('click', '.funding-agency-link', function(e) {
        e.preventDefault();
        if(e.target.dataset.termId) {
            const termId = e.target.dataset.termId;
            const programList = $('.program-list');

           // Remove 'current' class from all links
            $('.funding-agency-link').removeClass('current');
            
            // Add 'current' class to the clicked link
            $(this).addClass('current');

            programList.empty();

            if (jQuery('.loading-dual-ring').length === 0) {
                jQuery('.archive-programs').append('<div class="loading-dual-ring"></div>');
            }

            // Make a GET request to the endpoint
            $.ajax({
                url: '/wp-json/psi/v1/funding-programs/' + termId, // Adjust the URL based on your WordPress setup
                method: 'GET',
                data: { active: true },
                success: function(response) {
                    for(let i = 0; i < response.length; i++) {
                        programList.append(createProgramLink(response[i]));
                    }

                    jQuery('.loading-dual-ring').remove();
                    
                },
                error: function(xhr, status, error) {
                    console.error(error); // Log any errors
                    jQuery('.loading-dual-ring').remove();
                }
            });
        }
        // Perform additional actions here if needed
    });

    $('.program-list').on('click', '.funding-program-link', function(e) {
        e.preventDefault();
        if(e.target.dataset.programId) {
            const termId = e.target.dataset.programId;
            const termName = e.target.textContent;
            const projectList = $('.project-list');

            $('.funding-program-link').removeClass('current');
            
            // Add 'current' class to the clicked link
            $(this).addClass('current');

            projectList.empty();

            projectListTitle.text(`Projects For ${termName}`)

            if (jQuery('.loading-dual-ring').length === 0) {
                jQuery('.project-list').append('<div class="loading-dual-ring"></div>');
            }

            // Make a GET request to the endpoint
            $.ajax({
                url: '/wp-json/psi/v1/active-projects/', // Adjust the URL based on your WordPress setup
                method: 'GET',
                data: { program_id: termId },
                success: function(response) {
                    
                    projectList.append(response.html);
                    jQuery('.loading-dual-ring').remove();
                    
                },
                error: function(xhr, status, error) {
                    console.error(error); // Log any errors
                    jQuery('.loading-dual-ring').remove();
                }
            });
        }
        // Perform additional actions here if needed
    });
});

function createProgramLink(program) {
    let listItem = document.createElement('li');
    let link = document.createElement('a');
    
    link.setAttribute('href', '#');
    link.setAttribute('class', 'funding-program-link');
    link.setAttribute('data-program-id', program.id);
    link.textContent = program.name;
    
    listItem.appendChild(link);
    
    return listItem;
}