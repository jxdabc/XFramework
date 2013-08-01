function XTimer(interval, callback) 
{
	var timer = 
		setInterval(interval, function()
			{
				var rtn = callback();
				if (rtn === false) this.cancel();				
			});

	this.cancel = function () {
		if (timer === null)
			return;

		clearInterval(timer);
		timer = null; 
	}
}