"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Table").Element = class Element {
	constructor(value) {
		if (!window.Array.isArray(value))
			value = value.toString();
		else if (value.length === 0)
			value = "";
		Plushie.Utils.definePrivateProperties(this, "isArray", "isString", {
			isFiltered: { enumerable: true, value: false, writable: true },
			value: { enumerable: true, get: () => value }
		});
	}

	get isArray() { return Plushie.Utils.getPrivateProperty(this, "isArray", () => window.Array.isArray(this.value)); }
	get isString() { return Plushie.Utils.getPrivateProperty(this, "isString", () => typeof this.value === "string"); }
	get length() { return this.value.length; }

	_process(ifStringFunction = undefined, ifArrayFunction = undefined, ...args) {
		if (this.isString && ifStringFunction instanceof window.Function)
			return ifStringFunction(...args);
		else if (this.isArray && ifArrayFunction instanceof window.Function)
			return ifArrayFunction(...args);
		return undefined;
	}

	addToSet(set) {
		if (set && set.add)
			return this._process(() => set.add(this.value), () => this.value.reduce((set, value) => set.add(value), set));
		return undefined;
	}

	has(value) { return this._process(() => this.value === value, () => this.value.includes(value)); }
	join(separator = Element.DEFAULT_SEPARATOR) { return this._process(() => this.value, () => this.value.join(separator.toString())); }

	setIsFiltered(allowedValues) {
		if (allowedValues && allowedValues.some)
			this.isFiltered = !allowedValues.some((value) => this.has(value));
	}

	toNumber() { return this.toString().replace(/[^0-9.]/g, "") | 0; }
	toString(separator = Element.DEFAULT_SEPARATOR) { return this.join(separator); }
	[Symbol.toPrimitive](hint) { return (hint === "number") ? this.toNumber() : this.toString(); }
}
window.Object.defineProperties(Plushie.Plushmancer.Table.Element, { DEFAULT_SEPARATOR: { enumerable: true, get: () => ", " } });
window.Object.defineProperties(Plushie.Plushmancer.Table, { Element: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Table/Element");