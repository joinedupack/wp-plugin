jQuery(function($) {

  // Message passing logic for site previews
  var titleElement =  document.getElementById('site_title'); 
  var taglineElement =  document.getElementById('site_tagline'); 

  window.addEventListener("message", (event) => {

    if(event.data['blog_title'])
      titleElement.innerText = event.data['blog_title'];

    if(event.data['site_tagline'])
      taglineElement.innerText = event.data['site_tagline'];

  }, false);

  // Remove admin bar and extra spacing styles
  var adminBar = document.getElementById('wpadminbar');
  adminBar.parentNode.removeChild(adminBar);
  var htmlElement = document.getElementsByTagName('html')[0]
  htmlElement.style.setProperty('margin-top', '0px', 'important');
  htmlElement.style.setProperty('padding-top', '0px', 'important');

});