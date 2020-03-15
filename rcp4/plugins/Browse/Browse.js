/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
* @version 4.0.3.6
*/
function Browse_startup()
{
	//Load queue
	browse_Linkinizer();
	browse_loadq();
}

function browse_Linkinizer()
{
	//checkall checkbox
	$$('input.browsechkall').each(function(input)
	{
		input.removeEvents('click');
		input.addEvent('click', function(e)
		{
			$$('input.browsechk').each(function(checkbox)
			{
				checkbox.set('checked', input.get('checked'));
				if(checkbox.get('checked')) {
					browse_addq(checkbox.get('rel'));
				} else {
					browse_remq(checkbox.get('rel'));
				}
			});
			browse_loadq();
		});
	});

	//other checkbox
	$$('input.browsechk').each(function(input)
	{
		//check selected state, set it if file is allready in queue
		if(browse_searchq(input.get('rel'))) {
			input.set('checked', true);
		} else {
			input.set('checked', false);
		}

		input.removeEvents('click');
		input.addEvent('click', function(e)
		{
			if(input.get('checked')) {
				browse_addq(input.get('rel'));
			} else {
				browse_remq(input.get('rel'));
			}
			browse_loadq();
		});
	});
}

function browse_updatefiles()
{
	browse_cdir(false, false, 'name|0');
}

function browse_cdir(obj, dir, sort, querystring)
{
	if(obj != false) {
		$$($('mainarea').getElementsByTagName('a')).each(function(link)
		{
			link.erase('class');
		});
		$(obj).set('class', 'bold');
	}

	if(!$defined(querystring)) {
		querystring = '';
	}

	if(dir == false) {
		dir = $('formdir').get('value');
	}

	var request = new Request.HTML(
	{
		update: $('browsecontent'),
		onSuccess: function(responseText, responseXML)
		{
			rcp_Actions.Linkinizer();
			browse_Linkinizer();
		}
	});
	request.get('ajax.php?plugin=Browse&op=getdata&filesort=' + sort + '&open=' + dir + querystring);

	$('formdir').set('value', dir);
	$('updir').set('value', dir);
	$('msdir').set('value', dir);
	$('ssdir').set('value', dir);
}

/*
 * Queue Functions
 */
Array.prototype.clear=function()
{
	this.length = 0;
};
var browse_q = new Array();

function browse_searchq(file)
{
	for(i=browse_q.length-1;0 !=i % 4;i--)
	{
		if(browse_q[i] == file) {
			return i;
		}
	}
	return false;
}

function browse_addq(file)
{
	browse_q.push(file);
}

function browse_remq(file)
{
	var id = browse_searchq(file);
	browse_q.splice(id, 1);
}

function browse_emptyq()
{
	browse_q.clear();
	browse_loadq();
}

function browse_loadq()
{
	var queue = $('browsequeue');
	queue.set('html', '');

	for (var i = 0; i < browse_q.length; i++)
	{
		if($defined(browse_q[i])) {
			queue.set('html', queue.get('html')+"<a href='#' onclick=\"browse_remq('"+ browse_q[i] +"'); browse_loadq(); return false;\"><input type='hidden' value='" + browse_q[i] + "' name='" + i + "' />" + browse_q[i] + "</a><br />");
		}
	}
}

/*
 * Upload functions
 */
function browse_iframeremove(frm)
{
	frm.parentNode.removeChild(frm);
}

function browse_addEvent(obj, evType, fn)
{
	if (obj.addEventListener) {
		obj.addEventListener(evType, fn, true);
	}

	if (obj.attachEvent) {
		obj.attachEvent('on'+evType, fn);
	}
}

function browse_removeEvent(obj, type, fn)
{
	if (obj.detachEvent) {
		obj.detachEvent('on'+type, fn);
	} else {
		obj.removeEventListener(type, fn, false);
	}
}

//needs mootoolifikation -.- ^^
function browse_upload(form,url_action,id_element,html_show_loading,html_error_http)
{
	form = typeof(form) == "string" ? $(form) : form;
	form = $(form);
	var erro = "";
	if(!$defined(form)) {
		erro += "The form of 1st parameter does not exists.\n";
	} else if(form.nodeName!="FORM") {
		erro += "The form of 1st parameter its not a form.\n";
	}

	if($(id_element) == null) {
		erro += "The element of 3rd parameter does not exists.\n";
	}

	if(erro.length > 0) {
		alert("Error at upload call:\n" + erro);
		return;
	}

	//creating the iframe
	var iframe = $(document.createElement('iframe'));
	iframe.set('id', 'up-temp');
	iframe.set('name', 'up-temp');
	iframe.set('width', 0);
	iframe.set('height', 0);
	iframe.set('border', 0);
	iframe.setStyle('width', 0);
	iframe.setStyle('height', 0);
	iframe.setStyle('border', 0);

	//add to document
	form.parentNode.appendChild(iframe);
	window.frames['up-temp'].name = 'up-temp'; //ie sucks

	//add event
	var carregou = function()
	{
		browse_removeEvent($('up-temp'), "load", carregou);
		var cross = "javascript: ";
		cross += "window.parent.document.getElementById('" + id_element + "').innerHTML = document.body.innerHTML; void(0); ";
		$(id_element).set('html', html_error_http);
		$('up-temp').set('src', cross);

		//del the iframe
		setTimeout(function(){ browse_iframeremove($('up-temp'))}, 250);

		//enabling form elements
		for(var i = 0;i < form.elements.length;i++)
		{
			if(typeof(form.elements[i].type) != "undefined") {
				form.elements[i].disabled = false;
			}
		}

		//reload files
		browse_updatefiles();
	};
	browse_addEvent($('up-temp'), "load", carregou);

	//properties of form
	form.set('target','up-temp');
	form.set('action',url_action);
	form.set('method','post');
	form.set('enctype','multipart/form-data');
	form.set('encoding','multipart/form-data');

	//submit
	form.submit();

	//disabling form elements
	for(var i = 0;i < form.elements.length;i++)
	{
		if(typeof(form.elements[i].type) != "undefined") {
			form.elements[i].disabled = true;
		}
	}

	//while loading
	if(html_show_loading.length > 0) {
		$(id_element).set('html', html_show_loading);
	}
}