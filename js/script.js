let articleArgs = {
    small: 2,
    medium: 3,
    large: 4,
    xl: 2,
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
}

let projectArgs = {
    small: 2,
    medium: 4,
    large: 4,
    xl: 3,
    lazyLoad: 'ondemand',
    responsive: [
        {
            breakpoint: 1224,
            settings: {
              slidesToShow: 3,
              slidesToScroll: 3,
              infinite: true,
            }
        },
        {
            breakpoint: 750,
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
}

class RelatedUserArticles {
    constructor() {
        this.userID = jQuery('[data-user]').data('user');
        this.loadMorePosts = jQuery('#load-more-related-posts');
        this.loadingSpinner = jQuery('<div class="loading-dual-ring"></div>');
        this.resultsContainer = jQuery('#related-posts-grid');
        this.loaderContainer = jQuery('.loader-container');
        this.postsPage = 1;
        this.postsToGet = 6;
        this.postsToSkip = 0;

        this.loadMorePosts.on('click', this.getRelatedPosts.bind(this));

    }

    getRelatedPosts() { 
        this.loaderContainer.append(this.loadingSpinner);
    
            jQuery.ajax({
                url: '/wp-json/psi/v1/user-related-posts/' + this.userID,
                method: 'GET',
                contentType: 'application/json;charset=UTF-8',
                data:{
                    userID: this.userID,
                    page: this.postsPage,
                    amount: this.postsToGet,
                    skip: this.postsToSkip
                },
                success: data => {
                    // Remove the loading spinner
                    this.postsPage++;
                    this.loaderContainer.empty();
    
                    // Check if there is HTML content
                    
                    if (data && data.html) {
                        
                        if (this.resultsContainer.length) {
                            this.resultsContainer.append(data.html);
                            initializeSlickSlider(`.related-staff-container.page${this.postsPage - 1}`, articleArgs);
                        }
    
                        // Check if there are more posts
                        if (!data.has_more) {
                            // Remove the load more button if there are no more posts
                            this.loadMorePosts.remove();
                        }
                    }
                },
                error: error => {
                    // Remove the loading spinner in case of an error
                    this.loaderContainer.empty();
                    console.log('Error:', error);
                },
            });
    }
}

class RelatedUserProjects {
    constructor() {
        this.userID = jQuery('[data-user]').data('user');
        this.showingActive = true;
        this.projectsHeadline = jQuery('#projects-headline');
        this.swapProjectsBtn = jQuery('.swap-projects');
        this.pastProjectsBtn = jQuery('#past-projects');
        this.projectLoaderContainer = jQuery('.project-loader-container');
        this.loadMoreProjects = jQuery('#load-more-related-projects');
        this.loadingSpinner = jQuery('<div class="loading-dual-ring"></div>');
        this.resultsContainer = jQuery('#related-projects-container');
        this.loaderContainer = jQuery('.project-loader-container');
        this.loading = false;
        this.activeProjectsPage = 1;
        this.activeProjectsToGet = 4;
        this.activeProjectsToSkip = 0;
        this.pastProjectsPage = 0;
        this.pastProjectsToGet = 4;
        this.pastProjectsToSkip = 0;

        //this.swapProjectsBtn.text(this.showingActive ? 'Active Projects' : 'Past Projects')

        // Add event listeners
        this.swapProjectsBtn.on('click', this.swapProjects.bind(this));
        //this.pastProjectsBtn.on('click', this.getPastUserProjects.bind(this));

        this.loadMoreProjects.on('click', () => {
            if (this.showingActive) {
                this.getActiveUserProjects.bind(this)();
            } else {
                this.getPastUserProjects();
            }
        });
    }

    swapProjects() {
        
        
        this.showingActive = !this.showingActive;
        this.swapProjectsBtn.text(this.showingActive ? 'Past Projects' : 'Active Projects');
        this.projectsHeadline.text(this.showingActive ? 'Active Projects' : 'Past Projects');
        this.loadMoreProjects.remove()
        this.resetPageData.bind(this)();
        this.resultsContainer.empty();
        if(!this.showingActive) {
            this.getPastUserProjects.bind(this)();
        } else {
            this.getActiveUserProjects.bind(this)();
        }
    }

    initLoadMore() {
        let container = this.projectLoaderContainer;
        this.loadMoreProjects = jQuery('<div id="load-more-related-projects">Load More<i class="fa-solid fa-angle-right"></i></div>');
        container.append(this.loadMoreProjects);
        this.loadMoreProjects.on('click', () => {
            if (this.showingActive) {
                this.getActiveUserProjects();
            } else {
                this.getPastUserProjects();
            }
        });
    }

    getPastUserProjects() {
        
        let loadingSpinner = jQuery('<div class="loading-dual-ring"></div>'); 

        this.loading = true;
        this.loadMoreProjects.hide();
        
        this.loaderContainer.prepend(loadingSpinner);

        jQuery.ajax({
            url: '/wp-json/psi/v1/past-user-projects/',
            method: 'GET',
            contentType: 'application/json;charset=UTF-8',
            data:{
                userID: this.userID,
                page: this.pastProjectsPage,
                amount: this.pastProjectsToGet,
                skip: this.pastProjectsToSkip
            },
            success: data => {
                this.loading = false;
                this.loadMoreProjects.show();
                this.pastProjectsPage++;
                
                loadingSpinner.remove();
            
                // Check if there is HTML content
                if (data && data.html) {

                    this.resultsContainer.append(data.html);

                    // Check if there are more posts
                    if (!data.has_more) {
                        // Remove the load more button if there are no more posts
                        this.loadMoreProjects.remove();
                    }
                }
            },
            error: error => {
                // Remove the loading spinner in case of an error
                this.loading = false;
                this.loadMoreProjects.show();
                loadingSpinner.remove();
                console.log('Error:', error);
            },
        });
    }

    getActiveUserProjects() {
        let loadingSpinner = jQuery('<div class="loading-dual-ring"></div>'); 

        this.loading = true;
        this.loadMoreProjects.hide();
        
        this.loaderContainer.prepend(loadingSpinner);

        jQuery.ajax({
            url: '/wp-json/psi/v1/active-user-projects/',
            method: 'GET',
            contentType: 'application/json;charset=UTF-8',
            data:{
                userID: this.userID,
                page: this.activeProjectsPage,
                amount: this.activeProjectsToGet,
                skip: this.activeProjectsToSkip
            },
            success: data => {
                // Remove the loading spinner
                this.loading = false;
                this.loadMoreProjects.show();
                this.activeProjectsPage++;
                
                loadingSpinner.remove();
            
                // Check if there is HTML content
                if (data && data.html) {

                    this.resultsContainer.append(data.html);

                    // Check if there are more posts
                    if (!data.has_more) {
                        // Remove the load more button if there are no more posts
                        this.loadMoreProjects.remove();
                    }
                }
            },
            error: error => {
                // Remove the loading spinner in case of an error
                this.loading = false;
                this.loadMoreProjects.show();
                loadingSpinner.remove();
                console.log('Error:', error);
            },
        });
    }

    resetPageData() {
        if(!this.showingActive) {
            this.pastProjectsPage = 0;
        } else {
            this.activeProjectsPage = 0;
        }
        
        this.initLoadMore.bind(this)();
    }

    handleLoadingState() {
        if(this.loading) {
            this.loading = false;
        } else {
            this.loading = true;
        }


    }
}

jQuery(document).ready(function ($) {
    new RelatedUserProjects();
    new RelatedUserArticles();
    // Initialize the first slider
    initializeSlickSlider('.related-staff-container', articleArgs);
    initializeSlickSlider('.collaborators-container', projectArgs);

});





function initializeSlickSlider(target, slideArgs) {
    jQuery(target).slick({
        infinite: true,
        slidesToShow: slideArgs.medium,
        slidesToScroll: slideArgs.medium,
        prevArrow: '<i class="fa-solid fa-angle-left prev"></i>',
        nextArrow: '<i class="fa-solid fa-angle-right next"></i>',
        lazyLoad: 'ondemand',
        responsive: slideArgs.responsive
    });
}

