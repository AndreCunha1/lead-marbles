/*
[Filename] YouTube_AutoPresenceClicker.js
[Description] Auto clicks that "I'm still here" button while you are busy tidying your room.
*/
let auto_clicker_id = setInterval(() => {
	var buttons = document.querySelectorAll('[role=\"dialog\"] [defer-on-watch] [role=\"button\"]');
	if (buttons && (buttons.length === 1)) {
		for (var i = buttons[0]; i && (i.getAttribute('role') !== 'dialog'); i = i.parentElement);
		if (i && (i.style.display !== 'none')) {
			console.log('You (hopefully) have been spared!', buttons[0]);
			buttons[0].click();
		}
	}
}, 4000);
