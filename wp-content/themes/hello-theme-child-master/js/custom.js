jQuery(document).ready(function () {


document.querySelectorAll('.uc_logo_marquee img').forEach(img => {
  function setWidth() {
    img.style.width = (img.naturalWidth / 2) + 'px';
  }
  if (img.complete) {
    setWidth();
  } else {
    img.addEventListener('load', setWidth);
  }
});
    


});


