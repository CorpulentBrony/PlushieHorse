"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Table").Column = class Column extends window.Array {
	constructor(...args) {
		super(...args);
		Plushie.Utils.definePrivateProperties(this, "uniqueValues", "width", {
			_header: { value: undefined, writable: true }
		});
	}

	get header() { return this._header; }
	get name() { return this.header.textContent; }

	get uniqueValues() { 
		return Plushie.Utils.getPrivateProperty(this, "uniqueValues", () => {
			const uniqueValues = window.Array.from(super.reduce((values, cell) => cell.addToSet(values), new window.Set())).sort();
			return uniqueValues.map((value, index) => { return { index, value: value.toLowerCase() }; }).sort((a, b) => (a.value > b.value) ? 1 : (a.value < b.value) ? -1 : 0).map((value) => uniqueValues[value.index]);
		});
	}

	get width() { return Plushie.Utils.getPrivateProperty(this, "width", () => this.header.offsetWidth); }

	set header(header) {
		if (!(header instanceof window.HTMLTableCellElement))
			return;
		window.Object.defineProperties(this, { _header: { value: header, writable: false } });
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Table, { Column: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Table/Column");