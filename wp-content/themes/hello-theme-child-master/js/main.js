// Find all elements with class blaze-slider
//const blazeSliders = document.querySelectorAll('.blaze-slider');
const blazeSliders = document.querySelectorAll('.blaze-slider .elementor-loop-container');

// slider nav under
// blazeSliders.forEach(slider => {
//   // Get all direct children of blaze-slider
//   const slides = Array.from(slider.children);
  
//   // Create the wrapper elements
//   const blazeContainer = document.createElement('div');
//   blazeContainer.className = 'blaze-container';
  
//   const trackContainer = document.createElement('div');
//   trackContainer.className = 'blaze-track-container';
  
//   const track = document.createElement('div');
//   track.className = 'blaze-track';
  
//   // Move all slides into blaze-track
//   slides.forEach(slide => {
//       track.appendChild(slide);
//   });
  
//   // Assemble the track structure
//   trackContainer.appendChild(track);
//   blazeContainer.appendChild(trackContainer);
  
//   // Create inner div for buttons
//   const innerDiv = document.createElement('div');
//   innerDiv.className = 'inner';
  
//   // Create navigation buttons
//   const prevButton = document.createElement('button');
//   prevButton.className = 'blaze-prev';
//   prevButton.textContent = 'previous';
  
//   const nextButton = document.createElement('button');
//   nextButton.className = 'blaze-next';
//   nextButton.textContent = 'next';
  
//   // Add buttons to inner div
//   innerDiv.appendChild(prevButton);
//   innerDiv.appendChild(nextButton);
  
//   // Create pagination container
//   const pagination = document.createElement('div');
//   pagination.className = 'blaze-pagination';
  
//   // Add elements to blaze-container
//   blazeContainer.appendChild(innerDiv);
//   blazeContainer.appendChild(pagination);
  
//   // Add the complete structure to blaze-slider
//   slider.appendChild(blazeContainer);
// });

// slider nav above
blazeSliders.forEach(slider => {
  // Get all direct children of blaze-slider
  const slides = Array.from(slider.children);
  
  // Create the wrapper elements
  const blazeContainer = document.createElement('div');
  blazeContainer.className = 'blaze-container';
  
  const trackContainer = document.createElement('div');
  trackContainer.className = 'blaze-track-container';
  
  const track = document.createElement('div');
  track.className = 'blaze-track';
  
  // Move all slides into blaze-track
  slides.forEach(slide => {
      track.appendChild(slide);
  });
  
  // Create inner div for buttons
  const innerDiv = document.createElement('div');
  innerDiv.className = 'inner';
  
  // Create navigation buttons
  const prevButton = document.createElement('button');
  prevButton.className = 'blaze-prev';
  prevButton.textContent = 'previous';
  
  const nextButton = document.createElement('button');
  nextButton.className = 'blaze-next';
  nextButton.textContent = 'next';
  
  // Add buttons to inner div
  innerDiv.appendChild(prevButton);
  innerDiv.appendChild(nextButton);
  
  // Assemble the track structure
  trackContainer.appendChild(track);
  
  // Create pagination container
  const pagination = document.createElement('div');
  pagination.className = 'blaze-pagination';
  
  // Add elements to blaze-container in new order
  blazeContainer.appendChild(innerDiv);
  blazeContainer.appendChild(trackContainer);
  blazeContainer.appendChild(pagination);
  
  // Add the complete structure to blaze-slider
  slider.appendChild(blazeContainer);

  // Remove any style tags within blaze-track
  const trackElements = track.querySelectorAll('style');
  trackElements.forEach(style => style.remove());
});

// Check if .blaze-slider exists and initialize it
const blazeSliderCheck = () => {
    //const blazeSlider = document.querySelector('.blaze-slider');
    const blazeSlider = document.querySelector('.blaze-slider .elementor-loop-container');
  
  if (blazeSlider) {
      // new BlazeSlider(blazeSlider, {
      //     all: {
      //         enableAutoplay: true,
      //         autoplayInterval: 2000,
      //         transitionDuration: 300,
      //         slidesToShow: 3,
      //     },
      //     '(max-width: 900px)': {
      //         slidesToShow: 2,
      //     },
      //     '(max-width: 500px)': {
      //         slidesToShow: 1,
      //     },
      // });
      
      // Remove any style tags in .blaze-track containers
      const tracks = document.querySelectorAll('.blaze-track');
      tracks.forEach(track => {
          const styles = track.querySelectorAll('style');
          styles.forEach(style => style.remove());
      });
  } 
};

document.addEventListener('DOMContentLoaded', function() {
  //blazeSliderCheck();
});

jQuery(document).ready(function () {

    jQuery(".wrapper-header").autoHidingNavbar({

    });

    var header = document.querySelector('.wrapper-header');
    var adminBar = document.getElementById('wpadminbar');
    var headerHeight = 0;
    var adminBarHeight = 0;

    if (header) {
    headerHeight = header.offsetHeight;
    }

    if (adminBar) {
    adminBarHeight = adminBar.offsetHeight;
    // Add admin bar height as margin-top to .wrapper-header
    header && header.style.setProperty('margin-top', adminBarHeight + 'px', 'important');
    }

    // Set the total margin-top on <html>
    var totalMarginTop = headerHeight + adminBarHeight;
    document.documentElement.style.setProperty('margin-top', totalMarginTop + 'px', 'important');

// Mobile Filter Dropdown Converter
(function() {
  'use strict';
  
  // Configuration
  const MOBILE_BREAKPOINT = 768;
  const FILTER_SELECTOR = 'search.e-filter';
  const BUTTON_SELECTOR = '.e-filter-item';
  
  // Check if the filter exists
  const filterContainer = document.querySelector(FILTER_SELECTOR);
  if (!filterContainer) {
      console.log('Filter container not found');
      return;
  }
  
  // Function to check if we're on mobile
  function isMobile() {
      return window.innerWidth < MOBILE_BREAKPOINT;
  }
  
  // Function to extract filter ID from current page URL or data attributes
  function getFilterId() {
      // Try to get from URL parameters first
      const urlParams = new URLSearchParams(window.location.search);
      for (const [key, value] of urlParams.entries()) {
          if (key.startsWith('e-filter-') && key.endsWith('-category')) {
              return key.replace('-category', '');
          }
      }
      
      // Look for the elementor widget with the data-id
      const elementorWidget = document.querySelector('.elementor-widget-loop-grid');
      if (elementorWidget && elementorWidget.dataset.id) {
          return `e-filter-${elementorWidget.dataset.id}`;
      }
      
      // Default fallback ID
      //return 'e-filter-0080563';
  }
  
  // Function to create dropdown
  function createDropdown() {
      const baseUrl = filterContainer.dataset.baseUrl || window.location.origin + window.location.pathname;
      const filterId = getFilterId();
      const buttons = filterContainer.querySelectorAll(BUTTON_SELECTOR);
      
      // Create select element
      const select = document.createElement('select');
      select.className = 'e-filter-mobile-dropdown';
      select.style.cssText = `
          width: 100%;
          padding: 10px;
          border: 1px solid #ccc;
          border-radius: 4px;
          background-color: white;
          font-size: 16px;
          appearance: none;
          background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23333" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
          background-repeat: no-repeat;
          background-position: right 10px center;
          background-size: 12px;
          padding-right: 30px;
      `;
      
      // Add onchange handler
      select.onchange = function() {
          if (this.value) {
              window.location.href = this.value;
          }
      };
      
      // Create default option
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'Select Category';
      select.appendChild(defaultOption);
      
      // Create options from buttons
      buttons.forEach(button => {
          const option = document.createElement('option');
          const filterValue = button.dataset.filter;
          
          // Build the URL
          let url;
          if (filterValue === 'all-news') {
              url = baseUrl;
          } else {
              const separator = baseUrl.includes('?') ? '&' : '?';
              url = `${baseUrl}${separator}${filterId}-category=${filterValue}`;
          }
          
          option.value = url;
          option.textContent = button.textContent.trim();
          
          // Check if this option should be selected based on current URL
          const currentUrl = window.location.href;
          if (currentUrl.includes(`${filterId}-category=${filterValue}`)) {
              option.selected = true;
          }
          
          select.appendChild(option);
      });
      
      return select;
  }
  
  // Function to show/hide elements based on screen size
  function toggleDisplay() {
      const dropdown = document.querySelector('.e-filter-mobile-dropdown');
      
      if (isMobile()) {
          // Hide original filter
          filterContainer.style.display = 'none';
          
          // Show dropdown or create it if it doesn't exist
          if (dropdown) {
              dropdown.style.display = 'block';
          } else {
              const newDropdown = createDropdown();
              filterContainer.parentNode.insertBefore(newDropdown, filterContainer.nextSibling);
          }
      } else {
          // Show original filter
          filterContainer.style.display = '';
          
          // Hide dropdown
          if (dropdown) {
              dropdown.style.display = 'none';
          }
      }
  }
  
  // Initialize
  toggleDisplay();
  
  // Listen for resize events
  let resizeTimer;
  window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(toggleDisplay, 100);
  });
  
  // Debug logging
  console.log('Mobile filter dropdown initialized');
  console.log('Filter ID detected:', getFilterId());
  console.log('Base URL:', filterContainer.dataset.baseUrl || window.location.origin + window.location.pathname);
  
})();










    setTimeout(function () {

        var isMobile = window.innerWidth < 1023;
        if(isMobile){
            // Find your loop carousel swiper instance
            var carousels = document.querySelectorAll('.autoplay-mobile .swiper');
            carousels.forEach(function(carousel) {
            if (carousel.swiper) {
                carousel.swiper.params.autoplay = {
                delay: 3000, // Set your desired speed here
                disableOnInteraction: false
                };
                carousel.swiper.autoplay.start();
            }
            });
        }
    
    }, 3000); // Delay to make sure Swiper is ready



}); //document ready end




