    /**
     * Resets the filters in a form and submits
     * 
     * @param form
     * @return
     */
    function synkResetFormFilters(form)
    {
        // loop through form elements
        for(i=0; i<form.elements.length; i++)
        {
            var string = form.elements[i].name;
            if ((string) && (string.substring(0,6) == 'filter'))
            {
                form.elements[i].value = '';
            }
        }
        form.submit();
    }
    
    /**
	 * 
	 * @param {Object} order
	 * @param {Object} dir
	 * @param {Object} task
	 */
	function gridOrdering( order, dir ) 
	{
		var form = document.adminForm;
	     
		form.filter_order.value     = order;
		form.filter_direction.value	= dir;
	
		form.submit();
	}
	
	/**
	 * 
	 * @param id
	 * @param change
	 * @return
	 */
	function gridOrder(id, change) 
	{
		var form = document.adminForm;
		
		form.id.value= id;
		form.order_change.value	= change;
		form.task.value = 'order';
		
		form.submit();
	}
	
	/**
	 * 
	 * @param {Object} divname
	 * @param {Object} spanname
	 * @param {Object} showtext
	 * @param {Object} hidetext
	 */
	function displayDiv (divname, spanname, showtext, hidetext) { 
		var div = document.getElementById(divname);
		var span = document.getElementById(spanname);
	
		if (div.style.display == "none")	{
			div.style.display = "";
			span.innerHTML = hidetext;
		} else {
			div.style.display = "none";
			span.innerHTML = showtext;
		}
	}
	
	/**
	 * 
	 * @param {Object} prefix
	 * @param {Object} newSuffix
	 */
	function switchDisplayDiv( prefix, newSuffix ){
		var newName = prefix + newSuffix;
		var currentSuffixDiv = document.getElementById('currentSuffix');
		var currentSuffix = currentSuffixDiv.innerHTML;	
		var oldName = prefix + currentSuffix;
		var newDiv = document.getElementById(newName);
		var oldDiv = document.getElementById(oldName);
	
		currentSuffixDiv.innerHTML = newSuffix;
		newDiv.style.display = "";
		oldDiv.style.display = "none";
	}

	/**
	 * 
	 * @param {Object} form
	 * @param {Object} task
	 * @param {Object} id
	 */
	function submitForm(form, task, id) 
	{   
		form.task.value = task;
		form.id.value = id;
		form.submit();
	}

	/**
	 * 
	 * @param {Object} form
	 * @param {Object} task
	 * @param {Object} id
	 */
	function verifySubmitForm( form, task, id, url ) {
		
		// if url is present, do validation
		if (url) 
		{		
			// loop through form elements and prepare an array of objects for passing to server
			var str = new Array();
			for(i=0; i<form.elements.length; i++)
			{
				postvar = {
					name : form.elements[i].name,
					value : form.elements[i].value,
					id : form.elements[i].id
				}
				str[i] = postvar;
			}

			// execute Ajax request to server
            var a=new Ajax(url,{
                method:"post",
				data:{"elements":Json.toString(str)},
                onComplete: function(response){
                    var resp=Json.evaluate(response);

                    if (resp.error == '1') 
                    {
                    	// display error message
                    	$("message-container").setHTML(resp.msg);
                    }
                    	else if (resp.error != '1') 
					{
						// if no error, submit form
						form.task.value = task;
						if (id) 
						{
							form.id.value = id;
						}
						form.submit();
					}
                }
            }).request();
		}	
			else 
		{
			form.task.value = task;
			if (id) 
			{
				form.id.value = id;
			}
			form.submit();
		}
	}	