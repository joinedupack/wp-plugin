jQuery(function($) {
 
  // Batch update templates
	$( '#submit-template_update' ).on( 'click', function (e) {
    e.preventDefault();
    
    // Gather data - array of {name, value} objs
    var site_rows = document.querySelectorAll('.site-row');
    var site_data = [];
    site_rows.forEach(function (row, i) {
      site_data.push({
        site_id: row.querySelector('.site-id').innerText,
        site_is_template: row.querySelector('.site-is_template>input').checked,
        site_is_discoverable: row.querySelector('.site-is_discoverable>input').checked
      });
    });
 
    // Send API request
		$.ajax({
			method: 'POST',
			url: EDUPACK.api.all_templates_url,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader('X-WP-Nonce', EDUPACK.api.nonce);
      },
      contentType: 'application/json',
			data: JSON.stringify(site_data)
		}).done( function (r) {
			$( '#template_update-response' ).html( '<p>' + EDUPACK.strings.updated + '</p>' );
		}).fail( function (r) {
			var message = EDUPACK.strings.error;
			if( r.hasOwnProperty( 'message' ) ){
				message = r.message;
			}
			$( '#template_update-response' ).html( '<p>' + message + '</p>' );
    });
    
    return false;
  });

  // Build new site from config
	$( '#submit-build' ).on( 'click', function (e) {
    e.preventDefault();
    
		var data = '';
 
		$.ajax({
			method: 'POST',
			url: EDUPACK.api.build_url,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader('X-WP-Nonce', EDUPACK.api.nonce);
			},
			data:data
		}).done( function (r) {
			$( '#feedback' ).html( '<p>' + EDUPACK.strings.built + '</p>' );
		}).fail( function (r) {
			var message = EDUPACK.strings.error;
			if( r.hasOwnProperty( 'message' ) ){
				message = r.message;
			}
			$( '#feedback' ).html( '<p>' + message + '</p>' );
    });

    return false;
  });

  
});