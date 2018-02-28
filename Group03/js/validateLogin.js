$(document).ready(function($){

  $('#login-form').submit(function() {

    var errorTable="";
    var firstError="";
    var fieldError=false;
    var errorColor="#ff0000";
    var normalColor="#000000";

    $('#form-submit').attr('disabled', 'disabled');
    $('#form-submit').attr('value', 'Validating ....');

    var formUID = $('#userId').val();
    var uidId = $('#userId').attr('id');
    var formPW = $('#password').val();
    var pwId = $('#password').attr('id');
		
    fieldError=false;
    if (formUID == "") {
      errorTable+="'User ID' must be entered\n";
      fieldError=true;
    } else if (formUID.length < 6 || formUID.length > 16) {
      errorTable+="'User ID' must be between 6-16 characters\n";
      fieldError=true;
    } else if (!alphaNumeric(formUID)) {
      errorTable+="'User ID' must be alphanumeric characters only\n";
      fieldError=true;
    }
    if (fieldError) {
      if (!firstError) firstError=uidId;
      $("#fidUserId").css("color", errorColor);
    } else {
      $("#fidUserId").css("color", normalColor);
    }

    fieldError=false;
    if (formPW == "") {
      errorTable+="'Password' must be entered\n";
      fieldError=true;
    } else if (formPW.length < 6 || formPW.length > 16) {
      errorTable+="'Password' must be between 6-16 characters\n";
      fieldError=true;
    } else if (!alphaNumeric(formPW)) {
      errorTable+="'Password' must be alphanumeric characters only\n";
      fieldError=true;
    }
    if (fieldError) {
      if (!firstError) firstError=pwId;
      $("#fidPassword").css("color", errorColor);
    } else {
      $("#fidPassword").css("color", normalColor);
    }

    // if no errors check login against the database
    var ajaxdata = 'ok';
    if (errorTable=="") {

      $.ajaxSetup({async: false});
      $.get('axchecklogin.php?theUID=' + formUID + '&thePW=' + formPW, function(data) {
        ajaxdata = data;
      });

      if (ajaxdata!="ok") {
        if (ajaxdata=="uiderror") {
          errorTable+="Invalid User ID\n";
          $("#fidUserId").css("color", errorColor);
          firstError=uidId;
        } else if (ajaxdata=="pwerror") {
          errorTable+="Invalid Password\n";
          $("#fidPassword").css("color", errorColor);
          firstError=pwId;
        }
      }

    }; // end if(errorTable=="")

    if (errorTable) {
      alert (errorTable);
      $('#form-submit').removeAttr("disabled");
//      $('#form-submit').attr('value', 'Log In');
			$('#'+firstError).focus();
      return false;
    } else { return true; }

  });

}); 
		
