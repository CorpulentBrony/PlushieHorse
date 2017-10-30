"use strict";
class PlushieEventTarget /* implements window.EventTarget */ {
	constructor() { window.Object.defineProperties(this, { listeners: { enumerable: true, value: new window.Map() } }); }

	addEventListener(type, callback) {
		if (!(callback instanceof window.Function))
			return;
		type = type.toString();

		if (!this.listeners.has(type))
			this.listeners.set(type, new window.Set());
		this.listeners.get(type).add(callback);
	}

	dispatchEvent(event) {
		if (!(event instanceof window.Event) || !this.listeners.has(event.type))
			return true;
		this.listeners.get(event.type).forEach((listener) => listener(event));
		return !event.defaultPrevented;
	}

	removeEventListener(type, callback) {
		if (!(callback instanceof window.Function) || !this.listeners.has(type))
			return;
		this.listeners.get(type).delete(callback);
	}
}
class PlushieArrayEventTarget extends window.Array /* with mix-in for PlushieEventTarget */ {
	constructor(...params) {
		super(...params);
		window.Object.defineProperties(this, { listeners: { enumerable: true, value: new window.Map() } });
	}
}
window.Object.getOwnPropertyNames(PlushieEventTarget.prototype).forEach((name) => {
	if (name !== "constructor")
		PlushieArrayEventTarget.prototype[name] = PlushieEventTarget.prototype[name];
});
class Plushie {
	static addClassToNamespace(parent, child, newClass) {
		if (!(parent instanceof window.Object && newClass instanceof window.Object))
			return undefined;
		child = child.toString();
		const properties = (parent[child] !== undefined) ? window.Object.getOwnPropertyDescriptors(parent[child]) : undefined;
		parent[child] = newClass;

		if (properties !== undefined)
			window.Object.defineProperties(parent[child], properties);
		return parent[child];
	}

	static addEventListener(...args) { return this.EVENT_DELEGATE.addEventListener(...args); }

	static checkForNamespace(parent, child, valueIfNotExists = {}) {
		if (!(parent instanceof window.Object && valueIfNotExists instanceof window.Object))
			return undefined;
		child = child.toString();

		if (parent[child] === undefined)
			parent[child] = valueIfNotExists;
		return parent[child];
	}

	static dispatchEvent(...args) { return this.EVENT_DELEGATE.dispatchEvent(...args); }

	static registerScript(script) {
		this.SCRIPTS.set(script, true);

		for (const [script, isLoaded] of this.SCRIPTS)
			if (!isLoaded)
				return;
		this.dispatchEvent(new window.CustomEvent("ready", { detail: { plushie: this, scripts: this.SCRIPTS } }));
	}

	static removeEventListener(...args) { return this.EVENT_DELEGATE.removeEventListener(...args); }
}
window.Object.defineProperties(Plushie, {
	EVENT_DELEGATE: { value: new PlushieEventTarget() },
	SCRIPTS: { enumerable: true, value: window.JSON.parse(window.document.getElementById("PlushmancerScripts").text).reduce((scripts, script) => scripts.set(script, false), new window.Map()) },
	ArrayEventTarget: { enumerable: true, value: PlushieArrayEventTarget },
	EventTarget: { enumerable: true, value: PlushieEventTarget },
	Plushmancer: { enumerable: true, value: window.Object.create(window.Object.prototype) }
});