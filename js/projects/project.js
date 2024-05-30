jQuery(document).ready(function ($) {
  const showMoreBtn = document.querySelector('.project-description-show-more');

  if(showMoreBtn){
    showMoreBtn.addEventListener('click', function(e) {
      e.preventDefault();
  
      const text = document.querySelector('.project-description-text');
  
      if(text.classList.contains('show')){
        text.classList.remove('show');
        text.classList.add('not-show');
        showMoreBtn.textContent = 'Show Less';
      } else {
        text.classList.remove('not-show');
        text.classList.add('show');
        showMoreBtn.textContent = 'Show More';
      }
  
      
    });
  
  }



    const projectWebsite = document.querySelector('.project-website');

    if(projectWebsite) {
        projectWebsite.addEventListener('mouseover', function() {
            showTooltip(this);
        });
    
        projectWebsite.addEventListener('mouseout', function() {
            hideTooltip(this);
        });
    }

    function showTooltip(element) {
        const tooltipText = element.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        element.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        const top = -tooltip.offsetHeight - 8;
        const left = rect.width / 2 - tooltip.offsetWidth / 2;

        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
      
       
      }
      
      function hideTooltip(element) {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
          element.removeChild(tooltip);
        }
      }

      initializeSlickSlider('.related-staff-container', articleArgs);
      new RelatedProjectArticles();

      
});

class RelatedProjectArticles {
  constructor() {
      this.projectID = jQuery('[data-project]').data('project');
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

      console.log('rrning')
  
          jQuery.ajax({
              url: '/wp-json/psi/v1/project-related-posts/' + this.projectID,
              method: 'GET',
              contentType: 'application/json;charset=UTF-8',
              data:{
                  projectID: this.projectID,
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

                      console.log(data);
  
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

let articleArgs = {
  small: 2,
  medium: 3,
  large: 4,
  xl: 2,
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