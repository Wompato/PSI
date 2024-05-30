/* jQuery(document).ready(function ($) {
    // Get all clickable program elements
    let programLinks = $('.clickable-program');
    const programList = $('.programs-list');
    const loadingSpinner = $('<div class="loading-dual-ring"></div>');

    programLinks.on('click', function () {
        let programId = $(this).data('program-id');
        // Show loading spinner while fetching projects
        showLoadingSpinner();

        // Fetch projects for the selected program
        fetchProjects(programId);
    });

    // Get all clickable agency elements
    let agencyLinks = $('.clickable-agency');

    agencyLinks.on('click', function () {
        let clickedAgency = $(this);

        programList.empty();

        // Show loading spinner while fetching child terms
        showLoadingSpinner();

        // Check if the clicked agency is already selected
        if (!clickedAgency.hasClass('selected')) {
            // Remove "selected" class from the currently selected agency
            $('.clickable-agency.selected').removeClass('selected');

            // Add "selected" class to the clicked agency
            clickedAgency.addClass('selected');

            let agencyId = clickedAgency.data('agency-id');

            // Fetch child terms for the selected agency
            fetchChildTerms(agencyId);
        }
    });

    function fetchProjects(programId) {
        // Use AJAX to fetch projects for the selected program
        let apiUrl = '/wp-json/psi/v1/projects?program_id=' + programId;

        $.ajax({
            url: apiUrl,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                // Update the content area with the fetched projects
                updateContentArea(data);
            },
            error: function (error) {
                console.error('Error fetching projects:', error);
            },
            complete: function () {
                // Hide loading spinner when the request is complete (success or error)
                hideLoadingSpinner();
            }
        });
    }

    function fetchChildTerms(agencyId) {
        // Use AJAX to fetch child terms for the selected agency
        let apiUrl = '/wp-json/psi/v1/child-terms?agency_id=' + agencyId;

        $.ajax({
            url: apiUrl,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                // Update the content area with the fetched child terms
                updateChildTerms(data);
            },
            error: function (error) {
                console.error('Error fetching child terms:', error);
            },
            complete: function () {
                // Hide loading spinner when the request is complete (success or error)
                hideLoadingSpinner();
            }
        });
    }

    function updateContentArea(data) {
        let projectList = $('#project-list');
        // Update the content area with the fetched projects HTML
        projectList.html(data.html);
    }

    function updateChildTerms(data) {
        let childTermsDisplay = $('.programs-list');
        // Update the content area with the fetched child terms HTML
        childTermsDisplay.html(data);
    }

    function showLoadingSpinner() {
        programList.append(loadingSpinner);
    }

    function hideLoadingSpinner() {
        loadingSpinner.remove();
    }
});
 */