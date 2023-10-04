$(document).ready(function() 
{
	$('select').on('change', function() 
	{
		this.style.color = this.options[this.selectedIndex].style.color;
		this.style.backgroundColor = this.options[this.selectedIndex].style.backgroundColor;
	});
});