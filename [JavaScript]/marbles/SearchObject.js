/*
[Filename] SearchObject.js
[Description] Recursively search the object for argument "key" or "value" and return an array of found references.
*/
Object.prototype.searchKeys = function (key) {
	let active = [this], seen = new Set(), found = new Set();
	while (active.length) {
		let next_active = [];
		for (let i = 0; i < active.length; ++i) {
			for (let j = 0, x, a = Object.keys(active[i]); j < a.length; ++j) {
				try {
					x = active[i][a[j]];
				} catch (e) {
					console.error(e);
					break; // skips current object on any property access error
				}
				if (a[j] === key && !found.has(active[i])) {
					console.log(`found[${found.size}]`);
					found.add(active[i]);
				} else if (x && (typeof x === "object") && !seen.has(x)) {
					seen.add(x);
					next_active.push(x);
				}
			}
		}
		active = next_active;
	}
	return Array.from(found);
};

Object.prototype.searchValues = function (value) {
	let active = [this], seen = new Set(), found = new Set();
	while (active.length) {
		let next_active = [];
		for (let i = 0; i < active.length; ++i) {
			for (let j = 0, x, a = Object.keys(active[i]); j < a.length; ++j) {
				try {
					x = active[i][a[j]];
				} catch (e) {
					console.error(e);
					break; // skips current object on any property access error
				}
				if (x === value && !found.has(active[i])) {
					console.log(`found[${found.size}]["${a[j]}"]`);
					found.add(active[i]);
				} else if (x && (typeof x === "object") && !seen.has(x)) {
					seen.add(x);
					next_active.push(x);
				}
			}
		}
		active = next_active;
	}
	return Array.from(found);
};
