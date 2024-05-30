class PostLoader {
    constructor(containerSelector, loaderContainerSelector, buttonSelector) {
        this.page = 0;
        this.container = jQuery(containerSelector);
        this.loaderContainer = jQuery(loaderContainerSelector);
        this.button = jQuery(buttonSelector);
        this.loadingSpinner = jQuery('<div class="loading-dual-ring"></div>');
        this.init();
    }

    init() {
        this.loadPosts();
    
        this.button.on('click', () => {
            this.loadPosts();
        });
    
        // Handle the search form submission
        jQuery('#advanced-search-form').on('submit', (e) => {
            e.preventDefault();
            this.container.html('');
            this.page = 0; // Reset to the first page
            this.loadPosts(true); // Pass true to indicate it's a new search
        });
    }

    showLoadingSpinner() {
        this.loaderContainer.append(this.loadingSpinner);
    }

    hideLoadingSpinner() {
        this.loadingSpinner.remove();
    }

    initSlick(target) {
        jQuery(target).slick({
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 3,
            prevArrow: '<i class="fa-solid fa-angle-left prev"></i>',
            nextArrow: '<i class="fa-solid fa-angle-right next"></i>',
            lazyLoad: 'ondemand',
            responsive: [
                {
                    breakpoint: 1224,
                    settings: {
                      slidesToShow: 2,
                      slidesToScroll: 2,
                      infinite: true,
                    }
                },
                {
                    breakpoint: 741,
                    settings: {
                      slidesToShow: 4,
                      slidesToScroll: 4,
                      infinite: true,
                    }
                },
                {
                    breakpoint: 540,
                    settings: {
                      slidesToShow: 3,
                      slidesToScroll: 3,
                      infinite: true,
                    }
                },
                {
                    breakpoint: 460,
                    settings: {
                      slidesToShow: 2,
                      slidesToScroll: 2,
                      infinite: true,
                    }
                }
              ]
        });
    }

    loadPosts(isNewSearch = false) {
        this.showLoadingSpinner();
        this.button.hide();
    
        const searchData = {
            post_type: load_more_params.post_type,
            posts_per_page: load_more_params.posts_per_page,
            category: load_more_params.category,
            page: this.page,
            search_keyword: jQuery('input[name="search_keyword"]').val(),
            start_date: jQuery('input[name="start_date"]').val(),
            end_date: jQuery('input[name="end_date"]').val()
        };
    
        jQuery.ajax({
            url: '/wp-json/psi/v1/load-more-posts/',
            data: searchData,
            type: 'get',
            success: (response) => {
                if (isNewSearch) {
                    this.container.html(''); // Clear previous posts if new search
                }
                if (response && response.html) {
                    this.container.append(response.html);
                    this.page++;
                    this.button.css('display', response.has_more ? 'block' : 'none');
                    this.initSlick(`.related-staff-carousel.page${this.page}`);
                }
                this.hideLoadingSpinner();
            },
            error: (error) => {
                console.log('Error:', error);
                this.hideLoadingSpinner();
            },
        });
    }
    
}

document.addEventListener("DOMContentLoaded", function() {

    new PostLoader('#load-more-posts-container', '.loader-container', '#load-more-posts-button');

    const form = document.getElementById('advanced-search-form');
    const clearButton = document.getElementById('clearButton');
    const inputs = document.querySelectorAll('.clearable');
    const toggleIcon = document.getElementById('toggleIcon');

    form.addEventListener('click', function(event) {
        console.log(event.target)
        if(event.target.id === 'advanced-search-form' || event.target.closest('#search-header') || event.target.closest('#toggleIcon')) {
            form.classList.toggle('expanded');
            toggleIcon.classList.toggle('icon-rotated');
        }
        
        event.stopPropagation();
    });

    clearButton.addEventListener('click', function() {
        inputs.forEach(input => {
            input.value = ''; 
        });
    });

});