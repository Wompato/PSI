jQuery(document).ready(function($) {
    const projectListTitle = $('.active-projects h4');

    const programList = $('.program-list');
    const projectList = $('.project-list');

    $('.agency-list').on('click', '.funding-agency-link', function(e) {
        e.preventDefault();
        if(e.target.dataset.termId) {
            const termId = e.target.dataset.termId;
            
           // Remove 'current' class from all links
            $('.funding-agency-link').removeClass('current');
            
            // Add 'current' class to the clicked link
            $(this).addClass('current');

            programList.empty();
            projectList.empty();
            projectListTitle.text('');

            if (jQuery('.loading-dual-ring').length === 0) {
                jQuery('.archive-programs').append('<div class="loading-dual-ring"></div>');
            }

            $.ajax({
                url: '/wp-json/psi/v1/funding-programs/' + termId,
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
        
    });

    $('.program-list').on('click', '.funding-program-link', function(e) {
        e.preventDefault();
        if(e.target.dataset.programId) {
            const termId = e.target.dataset.programId;
            const termName = e.target.textContent;
            

            $('.funding-program-link').removeClass('current');
            
            // Add 'current' class to the clicked link
            $(this).addClass('current');

            projectList.empty();

            projectListTitle.text(`Projects For ${termName}`)

            if (jQuery('.loading-dual-ring').length === 0) {
                jQuery('.project-list').append('<div class="loading-dual-ring"></div>');
            }

            $.ajax({
                url: '/wp-json/psi/v1/active-projects/',
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