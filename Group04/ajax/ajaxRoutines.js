function getRequest () {
  var http_request=false;
  if (window.XMLHttpRequest) { // Mozilla, Safari, ...
    http_request = new XMLHttpRequest();
    if (http_request.overrideMimeType) { http_request.overrideMimeType('text/xml'); }
  } else if (window.ActiveXObject) { // IE
    try {
      http_request = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        http_request = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) { http_request=false; }
    }
  }
  if (!http_request) { 
    alert('Giving up ... cannot create an XMLHTTP instance');
    return false;
  }
  return http_request;
}

function getValues (d,v) {
  http_request=getRequest();
  if (http_request) {
    var random_number=parseInt(Math.random()*99999999);
    var url='ajaxGetValues.php';
    url=url+'?v='+v;
    url=url+'&c='+d.axCountry.value;
    url=url+'&s='+d.axState.value;
    url=url+"&r="+random_number;
    http_request.open('GET', url, true);
    if (v=='s') {
      http_request.onreadystatechange = function () { setStateValues(http_request,d); };
    } else if (v=='c') {
      http_request.onreadystatechange = function () { setCityValues(http_request,d); };
    } else if (v=='r') {
      http_request.onreadystatechange = function () { setRegionValues(http_request,d); };
    }
    http_request.send(null);
	}
}

function setStateValues(http_request,d) {
  displayNames(document.locationform,'c');
  if (http_request.readyState==4 || http_request.readyState=="complete") {
    if (http_request.status == 200) {
      for (xx=d.axRegion.options.length-1;xx>=0;xx--) {
        d.axRegion.options[xx]=null;
      }
      d.axRegion[0]=new Option('Select a State', '');
      var each=new Array();
      for (xx=d.axState.options.length-1;xx>=0;xx--) {
        d.axState.options[xx]=null;
      }
      if (http_request.responseText!="") {
        var statesArray=new Array();
        statesArray=http_request.responseText.split("||");
        d.axState[0]=new Option('Select', '');
        for (xx=0;xx<statesArray.length;xx++) {
          each=statesArray[xx].split("::");
          yy=xx+1;
          d.axState[yy]=new Option(each[1], each[0]);
        }
      } else { d.axState[0]=new Option('Select a Country', ''); }
     
    } //  else { alert ('There was a problem with the ajax request'); }
  }
}

function setCityValues(http_request,d) {
  displayNames(document.locationform,'c');
  if (http_request.readyState==4 || http_request.readyState=="complete") {
    if (http_request.status == 200) {
      var each=new Array();
      for (xx=d.axCity.options.length-1;xx>=0;xx--) {
        d.axCity.options[xx]=null;
      }
      if (http_request.responseText!="") {
        var citiesArray=new Array();
        citiesArray=http_request.responseText.split("||");
        d.axCity[0]=new Option('Select', '');
        for (xx=0;xx<citiesArray.length;xx++) {
          each=citiesArray[xx].split("::");
          yy=xx+1;
          d.axCity[yy]=new Option(each[1], each[0]);
        }
      } else { d.axCity[0]=new Option('Select a Country', ''); }

    } //  else { alert ('There was a problem with the ajax request'); }
  }
}

function setRegionValues(http_request,d) {
  displayNames(document.locationform,'c');
  if (http_request.readyState==4 || http_request.readyState=="complete") {
    if (http_request.status == 200) {
      var each=new Array();
      for (xx=d.axRegion.options.length-1;xx>=0;xx--) {
        d.axRegion.options[xx]=null;
      }
      if (http_request.responseText!="") {
        var regionsArray=new Array();
        regionsArray=http_request.responseText.split("||");
        d.axRegion[0]=new Option('Select Whole State', '');
        for (xx=0;xx<regionsArray.length;xx++) {
          each=regionsArray[xx].split("::");
          yy=xx+1;
          d.axRegion[yy]=new Option(each[1], each[0]);
          cityNames[each[0]]=each[2];
        }

      } else { d.axRegion[0]=new Option('Select a State', ''); }
  
    } //  else { alert ('There was a problem with the ajax request'); }
  }
}

var cityNames = new Array();

function displayNames (d,v) {
  var cityArea = document.getElementById('cityNames');
  if (v=='s') {
    var cityIndex = d.axRegion.selectedIndex;
    if (cityIndex>0) {
//      document.getElementById('cityTable').style.display = 'inline';
      var cityValue = d.axRegion[d.axRegion.selectedIndex].value;
      var cityText = d.axRegion[d.axRegion.selectedIndex].text;
      cityArea.innerHTML = cityText + ' region includes ' + cityNames[cityValue] + ' and surrounding areas';
    } else { v='c'; }
  }
  if (v=='c') {
    cityArea.innerHTML = '';
//    document.getElementById('cityTable').style.display = 'none';
  }
}
