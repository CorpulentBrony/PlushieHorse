"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Table").Row = class Row extends window.Array {
	static from(element, array) {
		if (!window.Array.isArray(array))
			return undefined;
		const row = new this(element, array.length);
		array.forEach((element, index) => row[index] = element);
		return row;
	}

	constructor(element, size = 0) {
		super(size | 0);
		if (!(element instanceof window.HTMLTableRowElement))
			return;
		window.Object.defineProperties(this, { element: { enumerable: true, get: () => element } });
	}

	get visible() { return this.element.getAttribute("aria-hidden") == undefined; }

	set visible(isVisible) {
		if (window.Boolean(isVisible))
			this.show();
		else
			this.hide();
	}

	checkVisibility() { return !super.some((element) => element.isFiltered); }

	filter(column, allowedValues) {
		column |= 0;

		if (allowedValues && allowedValues.length === 0)
			this[column].isFiltered = false;
		else
			this[column].setIsFiltered(allowedValues);
		this.visible = this.checkVisibility();
	}

	hide() { this.element.setAttribute("aria-hidden", true); }
	show() { this.element.removeAttribute("aria-hidden"); }
}
window.Object.defineProperties(Plushie.Plushmancer.Table, { Row: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Table/Row");