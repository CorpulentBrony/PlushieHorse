"use strict";
Plushie.Utils = class Utils {
	static adoptElement(child, parent = undefined) {
		if (parent instanceof window.Node && child instanceof window.Node)
			parent.appendChild(child);
		return child;
	}

	static createElement(name, attributes = {}, parent = undefined, text = undefined) {
		name = name.toString();
		const result = this.elementSetAttributes(window.document.createElement(name), attributes);

		if (text && text.length)
			this.createTextNode(text, result);
		return this.adoptElement(result, parent);
	}

	static createTextNode(text, parent) {
		const node = window.document.createTextNode(text.toString());
		return this.adoptElement(node, parent);
	}

	static definePrivateProperties(object, ...properties) {
		if (object instanceof window.Object && properties && properties.reduce)
			window.Object.defineProperties(object, properties.reduce((descriptor, property) => {
				if (typeof property === "string")
					descriptor[`_${property}`] = this.PRIVATE_PROPERTY_DESCRIPTOR;
				else
					window.Object.assign(descriptor, property);
				return descriptor;
			}, window.Object.create(window.Object.prototype)));
		return object;
	}

	static elementSetAttributes(element, attributes = {}) {
		if (element instanceof window.Node && attributes instanceof window.Object)
			for (const property in attributes)
				element.setAttribute(property, attributes[property]);
		return element;
	}

	static generateRandomId(prefix = undefined, numDwords = 2, base = 36) {
		[prefix, numDwords, base] = [(prefix === undefined) ? "" : prefix.toString() + "-", numDwords | 0, window.Math.min(window.Math.max(base | 0, 2), 36)];
		const id = prefix + window.crypto.getRandomValues(new Uint32Array(numDwords)).reduce((result, num) => result + num.toString(base), "");

		if (window.document.getElementById(id) != null)
			return this.generateRandomId(prefix, numDwords, base);
		return id;
	}

	static getPrivateProperty(object, name, definitionFn) {
		name = name.toString();

		if (object instanceof window.Object && definitionFn instanceof window.Function)
			return (object[`_${name}`] !== undefined) ? object[`_${name}`] : object[`_${name}`] = definitionFn.call(object);
		return undefined;
	}

	static getScriptPath(script) { return `${this.ROOT_DIR}${script.toString()}.js`; }

	static loadScripts(scripts) {
		if (!scripts || !scripts.map)
			return window.Array.of(window.Promise.resolve(undefined));
		scripts = scripts.map((script) => this.getScriptPath(script));
		this.preload(scripts, "script", "application/javascript");
		return scripts.map((script) => new window.Promise((resolve, reject) => {
			const fragment = window.document.createDocumentFragment();
			const scriptElement = this.createElement("script", { async: true, src: script }, fragment);

			scriptElement.addEventListener("load", function onLoadListener() {
				resolve(scriptElement);
				scriptElement.removeEventListener("load", onLoadListener);
			});
			window.document.head.appendChild(fragment);
		}));
	}

	static mergeSets(...sets) {
		if (sets.length === 0 || !sets.every((set) => set instanceof window.Set))
			return undefined;
		return sets.reduce((merged, set) => {
			set.forEach((value) => merged.add(value));
			return merged;
		}, new window.Set(sets.pop()));
	}

	static numericIntCheck(value, valueIfInt = undefined, valueIfNotInt = undefined) {
		const valueParsed = window.Number.parseInt(value);

		if (valueIfInt === undefined)
			valueIfInt = valueParsed;

		if (valueIfNotInt === undefined)
			valueIfNotInt = value;
		return window.Number.isNaN(valueParsed) ? valueIfNotInt : valueIfInt;
	}

	static preload(files, as, type) {
		if (!files || !files.forEach)
			return;
		[as, type] = [as.toString(), type.toString()];
		const fragment = window.document.createDocumentFragment();
		files.forEach((file) => this.createElement("link", { as, href: file, rel: "preload", type }, fragment));
		window.document.head.appendChild(fragment);
	}

	static setImmediate(command) {
		if (!(command instanceof window.Function))
			return;
		else if (this._setImmediate !== undefined) {
			this._setImmediate(command);
			return;
		}
		else if (window.setImmediate !== undefined)
			this._setImmediate = window.setImmediate;
		else if (
			(() => {
				if (window.postMessage && !window.importScripts) {
					const oldOnMessage = window.onmessage;
					let postMessageIsAsynchronous = true;
					window.onmessage = () => postMessageIsAsynchronous = false;
					window.postMessage("", "*");
					window.onmessage = oldOnMessage;
					return postMessageIsAsynchronous;
				}
				return false;
			})()
		)
			this._setImmediate = (command) => {
				const messagePrefix = "Plushie.Utils.setImmediate$" + window.Math.random() + "$";
				const onGlobalMessage = (event) => {
					if (event.source === window && typeof event.data === "string" && event.data === messagePrefix) {
						command();
						window.removeEventListener("message", onGlobalMessage);
					}
				};
				window.addEventListener("message", onGlobalMessage, false);
				window.postMessage(messagePrefix, "*");
			};
		else if ("onreadystatechange" in window.document.createElement("script"))
			this._setImmediate = (command) => {
				const script = window.document.createElement("script");
				script.onreadystatechange = () => {
					command();
					script.onreadystatechange = undefined;
					window.document.head.removeChild(script);
					script = undefined;
				};
				window.document.head.appendChild(script);
			};
		else
			this._setImmediate = (command) => window.setTimeout(command, 0);
		this.setImmediate(command);
	}

	static toBoolean(value) { return (typeof value === "string") ? value.toLowerCase() !== "false" : Boolean(value); }
}
window.Object.defineProperties(Plushie.Utils, {
	PRIVATE_PROPERTY_DESCRIPTOR: { enumerable: true, get: () => { return { value: undefined, writable: true }; } },
	ROOT_DIR: { enumerable: true, get: () => "/extensions/PlushieHorse/js/" },
	_setImmediate: { value: undefined, writable: true }
});
window.Object.defineProperties(Plushie, { Utils: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Utils");