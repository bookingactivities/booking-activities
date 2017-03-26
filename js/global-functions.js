// Detect if the device used is touch-sensitive
function bookacti_is_touch_device() {
    var ua = navigator.userAgent;
    var is_touch_device = (
        ua.match(/iPad/i) ||
        ua.match(/iPhone/i) ||
        ua.match(/iPod/i) ||
        ua.match(/Android/i)
    );

    return is_touch_device;
}


// Add 0 before a number until it has *max* digits
function bookacti_pad( str, max ) {
  str = str.toString();
  return str.length < max ? bookacti_pad( "0" + str, max ) : str;
}


//Return true if mouse over the div
function bookacti_is_mouse_over_elem( elem ) {
    var ofs = elem.offset();
    var x1 = ofs.left;
    var x2 = ofs.left + elem.outerWidth();
    var y1 = ofs.top;
    var y2 = ofs.top + elem.outerHeight();

    if (currentMousePos.x >= x1 && currentMousePos.x <= x2 && currentMousePos.y >= y1 && currentMousePos.y <= y2) 
    {
        return true;
    }    

    return false; 
}


// Compare two arrays and tell if they are the same
function bookacti_compare_arrays( array1, array2 ) {
	
	var are_same = $j( array1 ).not( array2 ).length === 0 && $j( array2 ).not( array1 ).length === 0;
	
	return are_same;
}


// Serialize a form into a single object
$j.fn.serializeObject = function() {

	//Internet Explorer indxOf fix
	if(!Array.prototype.indexOf) {
		Array.prototype.indexOf = function(obj, start) {
			for (var i = (start || 0), j = this.length; i < j; i++) {
				if (this[i] === obj) { 
					return i; 
				}
			}
			return -1;
		};
	}

	var form_values = $j(this).serializeArray(),
		form_final = {};

	//fill object with results
	$j.each(form_values, function(){
		
		if( $j.isNumeric( this.value ) ) {
			this.value = parseFloat( this.value );
		}
		
		//Store Associated Array Input Array
		if(this.name.match(/\[(.+?)\]/g)){

			var arrayName = this.name.match(/\w+[^\[]/g)[0],
				propertyName = this.name.match(/\[(.+?)\]/g)[0];
				propertyName = propertyName.replace(/(\[|\])/g, "");

			if(!form_final.hasOwnProperty(arrayName)){
			   form_final[arrayName] = new Object(); 
			}
			
			if(this.name.indexOf('[' + propertyName +'][]') > 0){
				if(form_final[arrayName].hasOwnProperty(propertyName)){
				  form_final[arrayName][propertyName].push(this.value);
				} else {
				  form_final[arrayName][propertyName] = [ this.value ];
				}
			} else {
				form_final[arrayName][propertyName] = this.value;
			}

		// Store Array Input Array
		} else if(this.name.indexOf('[]') > 0){

		  //Remove [] from input name
		  this.name = this.name.split("[]")[0];

		  if(form_final.hasOwnProperty(this.name)){
			form_final[this.name].push(this.value);
		  } else {
			form_final[this.name] = [this.value];
		  }

		// Store multiple / checkboxes as Array
		} else if(form_final.hasOwnProperty(this.name)) {

		  if(typeof form_final[this.name] != 'object' ){  
			firstItem = form_final[this.name];
			form_final[this.name] = new Object();
			form_final[this.name][firstItem] = true;
		  }

		  form_final[this.name][this.value] = true;

		} else {

		  form_final[this.name] = this.value;

		}

	});

	//Output Object
	return form_final;
};