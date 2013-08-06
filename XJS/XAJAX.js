var XAJAX = 
{
	'ansyPostJSON' : function(target, obj, on_done, callback, callback_fail, after_done)
	{
		var ajax = null;
		try
		{
			ajax = new XMLHttpRequest(); // Firefox, Opera 8.0+, Safari
		}
		catch (e)
		{
			try
			{
				ajax = new ActiveXObject("Msxml2.XMLHTTP"); // Internet Explorer
			}
			catch (e)
			{
				try
				{
					ajax = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch (e)
				{
					alert("您的浏览器不支持AJAX，无法正常使用本页面！");
					throw 'CREATING AJAX FAILED';
				}
			}
		}

		if (JSON === undefined)
		{
			alert("未找到JSON2支持！");
			throw 'NO JSON2 SUPPORT';
		}

		_this = this;

		ajax.onreadystatechange = function ()
		{
			if (ajax.readyState == 4)
			{
				on_done();
				if (ajax.status == 200)
				{
					// chrome interupt. 
					if (ajax.responseText != '')
					{
						var obj = null;
						try
						{
							obj = JSON.parse(ajax.responseText);
						}
						catch (e)
						{
							obj = ajax.responseText;
						}
						
						try { console.log(obj); } catch (e) {}
						if (!_this.callInterceptors(obj)) callback (obj);
					}
				}
				else
				{
					try { console.log('' + ajax.status + ' ' + ajax.statusText + ' ' + ajax.responseText); } catch (e) {}
					_this.callFailHandlers(ajax.status, ajax.statusText);
					callback_fail (ajax.status, ajax.statusText);
				}
				after_done();
			}
		}

		var jsonstring = JSON.stringify(obj);

		ajax.open('POST', target, true);
		ajax.setRequestHeader('Content-Type', 'application/json');
		ajax.send(jsonstring);
	},

	'callFailHandlers' : function (status, statusText)
	{
		for (var i = 0; i < this.failHandlers.length; i++)
			this.failHandlers[i](status, statusText);
	},

	'callInterceptors' : function (obj)
	{
		for (var i = 0; i < this.interceptors.length; i++)
			if (this.interceptors[i](obj)) 
				return true;

		return false;
	},


	'registerFailHandler' : function (handler)
	{
		this.failHandlers.push(handler);
	},

	'removeFailHandler' : function (handler)
	{
		for (var i = 0; i < this.failHandlers.length;)
			if (this.failHandlers[i] == handler)
				this.failHandlers.splice(i, 1);
			else
				i++;

	},

	'registerInterceptor' : function (interceptor)
	{
		this.interceptors.push(interceptor);
	},

	'removeInterceptor' : function (interceptor)
	{
		for (var i = 0; i < this.interceptors.length;)
			if (this.interceptors[i] == interceptor)
				this.interceptors.splice(i, 1);
			else
				i++;
	},

	'failHandlers' : new Array(),
	'interceptors' : new Array(),
};