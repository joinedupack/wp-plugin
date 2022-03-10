jQuery(document).ready(function($) {

  // Variables
  var userInputIDs = ['blogname', 'blog_title', 'site_tagline'];
  var previewWindow = document.getElementById('template-iframe').contentWindow;
  var acceptableUse = document.getElementById('acceptable_use');
  var templateSelect = document.getElementById('template-select');

    // Only enable site creation button if a template is selected and privacy policy selected
    function updateFormSubmittability() {
      if(acceptableUse.checked && templateSelect.value !== 'none') {
        $( '#newblog-submit' ).prop( "disabled", false );
        $( '#newblog-submit' ).removeClass('disabled');
      } else {
        $( '#newblog-submit' ).prop( "disabled", true );
        $( '#newblog-submit' ).addClass('disabled');
      }
    }

  // Update preview and display loading animation
  $( '#template-select' ).on( 'change', function(e) {
    var baseSrc = this.querySelector('option:checked').getAttribute('data-url');
    var attributionElement = document.getElementById('site_attribution')
    document.getElementById('template-iframe').src = baseSrc;
    if(attributionElement) {
      var attribution = attributionElement.querySelector('option:checked').value;
      var attributionQuery = '&preview-attribution=' + attribution;
      document.getElementById('template-iframe').src += attributionQuery;
    } else{
      document.getElementById('template-iframe').src += '&preview-attribution=edupack_sites';
    }
    $( '#template-loading-container' ).show();
    $( '#template-iframe' ).show();
    updateFormSubmittability();
  });

  // Only enable site creation button if a template is selected and privacy policy selected
  $( '#acceptable_use' ).on( 'change', function(e) {
    updateFormSubmittability();
  });

  // Trigger initial preview load
  $( '#template-select' ).trigger( 'change' );

  // Trigger preview reload on attribution change
  $( '#site_attribution' ).on( 'change', function(e) {
    $( '#template-select' ).trigger( 'change' );
  });

  // Hide loading animation and pass initial values when preview is loaded
  $( '#template-iframe' ).on( 'load', function(e) {
    userInputIDs.forEach(element => {
      var data = { [element]: $('#' + element).val() };
      previewWindow.postMessage(data, '*');
    });
    $( '#template-loading-container' ).hide();
  });

  /**
   * Message passing for reactive updates
   * 
   * foreach reactive element input element
   * attach event listener
   * on change, post message to preview window
   * preview window will accept messages and reactively update
   */
  userInputIDs.forEach(element => {
    $('#' + element).on( 'keyup', function(event) {
      var data = { [element]: this.value };
      previewWindow.postMessage(data, '*');
    });
  });

  // Reactive updates for template preview address bar
  // TODO: Get the root URL in here.
  $( '#blogname' ).on( 'keyup', function(e) {
    var address = 'https://' + $( '#blogname' ).val() + $( '.group-suffix' ).first().text()
    $( '.header-address' ).first().text(address);
  });

  // Toggle Viewport Size
  function toggleViewport(selectedViewport) {
    $('#templater-preview').attr('data-viewport', selectedViewport);
  }
  $('#selector-desktop').click(function(){
    toggleViewport('desktop');  
  });
  $('#selector-tablet').click(function(){
    toggleViewport('tablet');  
  });
  $('#selector-mobile').click(function(){
    toggleViewport('mobile');  
  });

});