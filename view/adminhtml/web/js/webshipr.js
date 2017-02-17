    function createWebshiprOrder(_process_order){
        require(['jquery'], function($){

             $.ajax( {
                url: webshipr_create_order_url,
                data: {
                    form_key:           window.FORM_KEY,
                    magento_order_id:    $('#magento_order_id').val(),
                    shipping_rate_id:    $('#webshipr_shipping_rate').val(),
                    shipping_rate_label: $('#webshipr_shipping_rate option:selected').text(),
                    process_order:   	 _process_order,
                },
                type: 'POST',
                showLoader: true
            }).done(function(result) { 
                    
                    if(!result.success){
                    	var message = '<ul class = "message">'+result.msg+'</ul>';
                        document.getElementById('webshipr-api-results').innerHTML = message;
     
                    } else {

                        //Reload current page
                        location.reload();
                    }
            });

            return false;
        });
    }

    function updateWebshiprOrder(_process_order){
        require(['jquery'], function($){

             $.ajax( {
                url: webshipr_update_order_url,
                data: {
                    form_key:   		 window.FORM_KEY,
                    magento_order_id:    $('#magento_order_id').val(),
                    shipping_rate_id:    $('#webshipr_shipping_rate').val(),
                    shipping_rate_label: $('#webshipr_shipping_rate option:selected').text(),
                    process_order:   	 _process_order
                },
                type: 'POST',
                showLoader: true
            }).done(function(result) { 
                    
                    if(!result.success){
                    	var message = '<ul class = "message">'+result.msg+'</ul>';
                        document.getElementById('webshipr-api-results').innerHTML = message;
     
                    } else {

                    	//Reload current page
                        location.reload();
                    }
            });

            return false;
        });
    }