jQuery(function($) {

	$('#site_selector')
		.select2({
			ajax: {
				// eslint-disable-next-line no-undef
				url: choices.site_url + '/wp-admin/admin-ajax.php',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						q: params.term,
						action: 'edupack_sites'
					};
				},
				processResults: function( data ) {
					var options = [];
					if ( data ) {
						$.each( data, function( index, text ) {
							options.push( { id: text['key'], text: text['value']  } );
						});
					}
					return {
						results: options
					};
				},
				cache: true
			},
			minimumInputLength: 3
		});
});