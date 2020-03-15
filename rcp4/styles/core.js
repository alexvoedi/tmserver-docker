/**
* remoteCP 4
* ütf-8 release
*
* @package remoteCP
* @author hal.sascha
* @copyright (c) 2006-2009
*/

/**
 * Tabs
 */
rcp_Tabs = new Class(
{
	tabobjects: '',

	initialize: function()
	{
		// setup tabs
		var first;
		$$('ul.tabs').each(function(el)
		{
			first = true;
			$$(el.getElementsByTagName('li')).each(function(el)
			{
				var link = $(el.getElementsByTagName('a')[0]);

				// click event and load
				if(link.get('rel')) {
					// set url
					var url = link.get('href').replace(/^http:\/\/[^\/]+\//i, 'http://'+window.location.hostname+'/');
					link.setProperty('href', url);

					// click event
					link.addEvent('click', function(e)
					{
						new Event(e).stop();
						this.Load(link);
					}.bind(this));

					// load tab if it is the first one
					if(first) {
						this.Load(link);
						first = false;
					}
				}
			}.bind(this));
		}.bind(this));
	},

	Load: function(element)
	{
		// get attributes
		var rel  = element.get('rel');
		var ref  = element.get('ref');
		var href = element.get('href');

		// remove all sel classes
		$$(element.parentNode.parentNode.getElementsByTagName('li')).each(function(link)
		{
			link.removeClass('sel');
		});

		// set current sel class
		var selected = element.parentNode;
		$(selected).addClass('sel');

		// AJAX!
		element.set('send',
		{
			method: 'get',
			onRequest: function()
			{
				$(rel).addClass('loading');
			}.bind(this),
			onSuccess: function(responseText, responseXML)
			{
				$(rel).set('html', responseText);
				$(rel).removeClass('loading');
				this.loadAssets(element);
				rcp_Actions.Linkinizer();
			}.bind(this),
			onFailure: function()
			{
				$(rel).removeClass('loading');
				$(rel).set('html', 'failure @ ajax request');
			}.bind(this)
		});
		element.send(href);

		//Set plugin name into area title
		$(rel+'title').set('html', element.get('html'));
	},

	loadAssets: function(element)
	{
		//alert('loadAssets called');
		// get & check attribute
		var ref = element.get('ref');
		if(ref == null || ref == '') {
			return false;
		}
		//alert(ref);

		// load css / js files + startup callback and title change
		// asset directory example: ./plugins/Browse/Browse.js
		var objs = ref.split(/\s*,\s*/);
		for(var i = 0; i < objs.length; i++)
		{
			var file = objs[i];
				file = file.split(':');
			var url	 = file[0];
			var id	 = file[1];
			if(this.tabobjects.indexOf(url) == -1) {
				if(url.indexOf('.js') != -1) {
					new Asset.javascript(url);
					this.tabobjects += url+' ';
				} else if(url.indexOf('.css') != -1) {
					new Asset.css(url);
					this.tabobjects += url+' ';
				}
			}

			//Call startup callback
			rcp_Actions.callCallback(id+'_startup');
		}
	}
});

/**
 * Actions
 */
rcp_Actions = new Class(
{
	linkinizercalls: 0,

	initialize: function()
	{
		this.Linkinizer();
	},

	Linkinizer: function()
	{
		//this.linkinizercalls = this.linkinizercalls + 1;
		//alert('rcp_Actions linkinizer called ('+this.linkinizercalls+')');

		SqueezeBox.assign($$('a.modal'));

		$$('form.postcmd', 'form.postcmdc').each(function(form)
		{
			form.removeEvents('submit');
			form.addEvent('submit', function(e)
			{
				new Event(e).stop();

				if(form.get('class') == 'postcmdc') {
					if(confirm('Are you sure?')) this.PostLoad(form);
				} else {
					this.PostLoad(form);
				}
			}.bind(this));
		}.bind(this));

		$$('a.getcmd', 'a.getcmdc').each(function(link)
		{
			link.removeEvents('click');
			link.addEvent('click', function(e)
			{
				new Event(e).stop();

				if(link.get('class') == 'getcmdc') {
					if(confirm('Are you sure?')) this.GetLoad(link);
				} else {
					this.GetLoad(link);
				}
			}.bind(this));
		}.bind(this));

		$$('select.getcmd').each(function(select)
		{
			select.removeEvents('change');
			select.addEvent('change', function(e)
			{
				new Event(e).stop();
				var href  = select.get('href');
				var index = select.options.selectedIndex;
				var value = $(select.options[index]).get('value');
				select.set('href', href+value);
				this.GetLoad(select);
				select.set('href', href);
			}.bind(this));
		}.bind(this));

		$$('input.selrow').each(function(input)
		{
			input.removeEvents('click');
			input.addEvent('click', function(e)
			{
				this.selRow(input);
			}.bind(this));
		}.bind(this));

		$$('input.checkall').each(function(input)
		{
			input.removeEvents('click');
			input.addEvent('click', function(e)
			{
				var form = input.form;
				var z = 0;
				for(z=0; z<form.length;z++)
				{
					$(form[z]).set('checked', input.get('checked'));
					this.selRow($(form[z]), input.get('checked'));
				}
			}.bind(this));
		}.bind(this));

		$$('a.newwindow').each(function(link)
		{
			link.removeEvents('click');
			link.addEvent('click', function(e)
			{
				new Event(e).stop();

				var rel = link.get('rel');
					rel = rel.split(':');
				var NewWindow = window.open(link.get('href'),rel[0],"width="+rel[1]+",height="+rel[2]+",location=yes,resizable=yes,scrollbars=yes,status=yes");
				NewWindow.focus();
			}.bind(this));
		}.bind(this));
	},

	PeriodicalUpdate: function(url, loader, target, callback)
	{
		var request = new Request.HTML(
		{
			update: $(target),
			onRequest: function()
			{
				$(loader).addClass('loading');
			}.bind(this),
			onSuccess: function(responseText, responseXML)
			{
				$(loader).removeClass('loading');
				this.callCallback(callback);
			}.bind(this),
			onFailure: function()
			{
				$(loader).removeClass('loading');
			}.bind(this)
		});
		request.get(url);
	},

	PostLoad: function(element)
	{
		// get attributes
		var id  = element.get('id');
		var rel = element.get('rel');
			rel = rel.split(':');

		// AJAX!
		element.set('send',
		{
			method: 'post',
			data: element,
			onSuccess: function(responseText, responseXML)
			{
				$(rel[0]).set('html', responseText);
				this.Linkinizer();
				this.callCallback(rel[1]);
			}.bind(this)
		});
		element.send('ajax.php');
	},

	GetLoad: function(element)
	{
		// get attributes
		var rel  = element.get('rel');
		var href = element.get('href');

		// AJAX!
		element.set('send',
		{
			method: 'get',
			onSuccess: function(responseText, responseXML)
			{
				$(rel).set('html', responseText);
				this.Linkinizer();
			}.bind(this)
		});
		element.send(href);
	},

	selRow: function(input, force)
	{
		if(input.parentNode.parentNode.className != 'bg-s' || force) {
			input.parentNode.parentNode.className = 'bg-s';
		} else {
			input.parentNode.parentNode.className = '';
		}
	},

	callCallback: function(callback)
	{
		if(callback) {
			if(typeof window[callback] == 'function') {
				window[callback]();
			}
		}
	}
});

/**
 * Startup
 */
window.addEvent('domready', function()
{
	rcp_Actions = new rcp_Actions();
	rcp_Tabs    = new rcp_Tabs();
	(function(){ rcp_Actions.PeriodicalUpdate('ajax.php?plugin=PCore', 'serverstatus', 'serverstatus', false) }).periodical(10500);
	(function(){ rcp_Actions.PeriodicalUpdate('ajax.php?plugin=PCore&op=challenge', 'currentchallenge', 'currentchallenge', false) }).periodical(15250);
});