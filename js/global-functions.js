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


// Compare two arrays and tell if they are the same
function bookacti_compare_arrays( array1, array2 ) {
	
	var are_same = $j( array1 ).not( array2 ).length === 0 && $j( array2 ).not( array1 ).length === 0;
	
	return are_same;
}


// Serialize a form into a single object (works with multidimentionnal inputs of any depth)
$j.fn.serializeObject = function() {
	var data = {};

	function buildInputObject(arr, val) {
		if (arr.length < 1) {
			return val;  
		}
		var objkey = arr[0];
		if (objkey.slice(-1) == "]") {
			objkey = objkey.slice(0,-1);
		}  
		var result = {};
		if (arr.length == 1){
			result[objkey] = val;
		} else {
			arr.shift();
			var nestedVal = buildInputObject(arr,val);
			result[objkey] = nestedVal;
		}
		return result;
	}
	
	function gatherMultipleValues( that ) {
		var final_array = [];
		$j.each(that.serializeArray(), function( key, field ) {
			// Copy normal fields to final array without changes
			if( field.name.indexOf('[]') < 0 ){
				final_array.push( field );
				return true; // That's it, jump to next iteration
			}
			
			// Remove "[]" from the field name
			var field_name = field.name.split('[]')[0];

			// Add the field value in its array of values
			var has_value = false;
			$j.each( final_array, function( final_key, final_field ){
				if( final_field.name === field_name ) {
					has_value = true;
					final_array[ final_key ][ 'value' ].push( field.value );
				}
			});
			// If it doesn't exist yet, create the field's array of values
			if( ! has_value ) {
				final_array.push( { 'name': field_name, 'value': [ field.value ] } );
			}
		});
		return final_array;
	}
	
	// Manage fields allowing multiple values first (they contain "[]" in their name)
	var final_array = gatherMultipleValues( this );
	
	// Then, create the object
	$j.each(final_array, function() {
		var val = this.value;
		var c = this.name.split('[');
		var a = buildInputObject(c, val);
		$j.extend(true, data, a);
	});

	return data;
};